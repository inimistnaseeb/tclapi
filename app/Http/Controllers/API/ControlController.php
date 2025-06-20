<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ControlRequest;
use App\Mail\ControlEmail;
use App\Models\Client;
use App\Models\Control;
use App\Models\ControlOccurrence;
use App\Models\ControlRisk;
use App\Models\ControlType;
use App\Models\Department;
use App\Models\Frequency;
use App\Models\KeyType;
use App\Models\Reminder;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Illuminate\Support\Facades\Mail;

class ControlController extends Controller
{
    public function index(Request $request)
    {
        // Retrieve request data
        $reqData = $request->all();

        // Set pagination limit (assuming you're using paginate)
        $paginationLimit = 15; // Set this to your preferred limit

        // Handle export with support
        if (isset($reqData['exportwithsupport']['submit'])) {
            $this->exportWithSupport($reqData['export_id']);
        }

        // Filtering based on `type`
        if ($request->query('type') == 'od') {
            $statusConditions = ['status_id' => STATUS_OVERDUE];
        } elseif ($request->query('type') == 'ns') {
            $statusConditions = ['status_id' => STATUS_PENDING];
        } else {
            $statusConditions = ['status_id', '!=', STATUS_COMPLETE];
        }

        // Handle deleted filter
        $deleted = $request->query('deleted', 0);
        $deletedConditions = [];
        if ($deleted) {
            $deletedConditions = ['deleted' => [1, 0], 'deleted IS NULL'];
        }

        // Retrieve session control filters or apply default filters
        if (!$request->input('Control.filters')) {
            if (session()->has('control_filters')) {
                $reqData = session('control_filters');
            } else {
                $reqData['Control'] = [
                    'filters' => '1',
                    'users' => 'By User',
                    'user_id' => auth()->user()->id,
                    'submit' => 'Submit'
                ];
            }
        } else {
            if ($reqData['Control']['submit'] == 'Reset Filters') {
                session()->forget('control_filters');
                $reqData['Control'] = [
                    'filters' => '1',
                    'users' => 'By User',
                    'user_id' => auth()->user()->id,
                    'submit' => 'Submit'
                ];
            } else {
                session()->put('control_filters', $reqData);
            }
        }

        // Base query for fetching controls
        $controlsQuery = Control::query()
            ->where($statusConditions)
            ->groupBy('id')
            ->with(['user', 'controlType', 'controlRisk', 'frequency', 'status', 'department'])
            ->orderByRaw("1 * SUBSTRING_INDEX(control_number, '.', 1) ASC, 1 * SUBSTRING_INDEX(control_number, '.', -1) ASC");

        // Apply deleted filter if necessary
        if (!empty($deletedConditions)) {
            $controlsQuery->where($deletedConditions);
        }

        // Apply procedure ID filter
        if (!empty($reqData['Control']['procedure_id'])) {
            $procedureControls = ControlProcedure::where('procedure_id', $reqData['Control']['procedure_id'])->pluck('control_id');
            if ($procedureControls->isNotEmpty()) {
                $controlsQuery->whereIn('id', $procedureControls);
            } else {
                $controlsQuery->where('id', 'test'); // Placeholder, no results
            }
        }

        // Handle showing completed controls
        if ($request->query('completed')) {
            session(['Controls.show_completed' => $request->query('completed')]);
        }
        if (session('Controls.show_completed', 0)) {
            $controlsQuery->where('status_id', '!=', STATUS_COMPLETE);
        }

        // Filtering controls by user permissions
        $viewConditions = [];
        if (!auth()->user()->isAdmin()) {
            if (auth()->user()->view_team_compliance && !auth()->user()->view_all_compliance) {
                $viewConditions = [
                    ['department_id', auth()->user()->department_id],
                    ['creator_id', auth()->user()->id]
                ];
            } elseif (!auth()->user()->view_all_compliance && !auth()->user()->view_team_compliance) {
                $viewConditions = [
                    ['user_id', auth()->user()->id],
                    ['creator_id', auth()->user()->id],
                    ['backup_user0_id', auth()->user()->id]
                    // Add other backup user fields if needed
                ];
            }
        }

        if (!empty($viewConditions)) {
            $controlsQuery->where($viewConditions);
        }

        // Fetch controls with pagination
        $controls = $controlsQuery->paginate($paginationLimit);

        // Handle Export
        if (isset($reqData['Control']['submit']) && $reqData['Control']['submit'] == 'Export') {
            $exportOptions = [];
            if (!empty($reqData['Control']['selected_ids'])) {
                $exportOptions['id'] = explode(',', $reqData['Control']['selected_ids']);
            }

            $exportData = $this->exportControls($controlsQuery, !empty($reqData['Control']['export_occurrences']));
            $this->exportToCsv($exportData);
        }

        // Fetch related models for dropdowns or additional data
        $users = User::pluck('name', 'id');
        $departments = Department::orderBy('name', 'asc')->pluck('name', 'id');
        $controlTypes = ControlType::pluck('name', 'id');
        $procedures = Procedure::orderBy('title', 'asc')->pluck('title', 'id');
        $clients = Client::orderBy('fullname', 'asc')->pluck('fullname', 'id');

        // Render view with compacted data
        return view('controls.index', compact('controls', 'users', 'departments', 'controlTypes', 'procedures', 'clients'));
    }


    public function add(Request $request)
    {
        // Add validation to ensure required fields are present and valid
        $validatedData = $request->validate([
            'control.control_name' => 'required|string|max:255',
            'control.control_type_id' => 'required|integer',
            'control.control_risk_id' => 'required|integer',
            'control.control_number' => 'required|integer',
            'control.first_due_date' => 'required|date',
            'control.backup_user0_id' => 'nullable|integer', // Optional, nullable field
            // Add other validation rules as needed
        ]);

        // Fetch related data to be passed to the view
        $frequencies = Frequency::pluck('name', 'id');
        $clients = Client::orderBy('fullname', 'ASC')->pluck('fullname', 'id');
        $keyTypes = KeyType::pluck('key_type_title', 'id');
        $controlTypes = ControlType::orderBy('controltype', 'ASC')->pluck('controltype', 'id');
        $controlRisks = ControlRisk::pluck('controlrisk', 'id');
        $reminders = Reminder::pluck('reminder_title', 'id');
        $statuses = Status::pluck('status_title', 'id');
        $users = User::where('role_id', '!=', GUEST_STUDENT)->pluck('username', 'id');
        $departments = Department::orderBy('name', 'ASC')->pluck('name', 'id');

        if ($request->isMethod('post')) {
            $control = new Control();

            // Automatically generate control number if not provided
            $controlNumberAuto = $control->createVersion();
            if (empty($request->input('control.control_number'))) {
                $request->merge(['control_number' => $controlNumberAuto]);
            }
            $request->merge(['control.control_number_auto' => $controlNumberAuto]);

            // Set occurrences to 0 if not repeating
            if (!$request->input('control.repeats')) {
                $request->merge(['control.occurrences' => 0]);
            }

            // Handle 'never_due' case
            if ($request->input('control.never_due')) {
                $request->merge([
                    'control.frequency_id' => null,
                    'control.repeat_frequency' => null,
                    'control.repeat_month_by' => null,
                ]);
            }

            // Handle occurrences for specific repeat conditions
            if ($request->input('control.repeats') && in_array($request->input('control.repeat_ends_on'), ['until', 'never'])) {
                $request->merge(['control.occurrences' => 1]);
            }

            // Set the first_due_date
            $dueDate = $request->input('control.due_date');
            if (!empty($dueDate)) {
                $dueDateTimestamp = strtotime($dueDate);
                $todayTimestamp = strtotime(date('Y-m-d'));

                if ($dueDateTimestamp > $todayTimestamp && $request->input('control.status_id') == STATUS_OVERDUE) {
                    $request->merge(['control.status_id' => STATUS_INPROCESS]);
                } elseif ($dueDateTimestamp < $todayTimestamp) {
                    $request->merge(['control.status_id' => STATUS_OVERDUE]);
                } else {
                    $request->merge(['control.status_id' => STATUS_INPROCESS]);
                }
            } else {
                $request->merge(['control.status_id' => STATUS_NOT_STARTED]);
            }

            // Handle ControlProcedure data
            $controlProcedures = [];
            if (!empty($request->input('ControlProcedure.procedure_id'))) {
                foreach ($request->input('ControlProcedure.procedure_id') as $procedureId) {
                    $controlProcedures[] = ['procedure_id' => $procedureId];
                }
            }

            // Sort backup users (assuming you have a method to handle this)
            $this->sortBackupUsers($request);

            // Create the control instance
            $control->fill($request->input('control'));
            if ($control->save()) {
                // Save Control Procedures
                $control->procedures()->sync($controlProcedures);

                // Handle file uploads
                if ($request->hasFile('file_uploads')) {
                    $this->handleFileUploads($request, $control);
                }

                // Handle 'never_due' or repetitions
                if ($request->input('control.never_due')) {
                    $this->handleNeverDueAddEdit($control);
                } else {
                    $this->handleRepetitionsOnAddEdit($control);
                }

                // Send email notifications
                $this->doControlEmail($control);

                // Log the action
                $this->log("##authuser## added a control ##action-view##", 'system');

                // Clear cache
                Cache::tags('control_procedure')->flush();

                // Set success message
                return redirect()->route('controls.index')->with('success', 'The control has been saved.');
            } else {
                // Handle validation errors
                return back()->withErrors($control->getErrors())->withInput();
            }
        }

        // Return the view with necessary data
        return view('controls.add', compact(
            'frequencies',
            'clients',
            'keyTypes',
            'controlTypes',
            'controlRisks',
            'reminders',
            'statuses',
            'users',
            'departments'
        ));
    }


    public function exportWithSupport($export_id)
    {
        $ids = explode(',', $export_id);
        $counter = 1;

        foreach ($ids as $id) {
            // Define paths for attached files and destination folder
            $dest_folder = storage_path('app/public/tmp');
            $attached_files = storage_path("app/public/jquploads/files/controls/{$id}");

            // Check if the directory for attached files exists
            if (is_dir($attached_files)) {
                $objects = scandir($attached_files);
                $files = [];
                if (is_array($objects)) {
                    foreach ($objects as $file) {
                        $new_path = $attached_files . '/' . $file;
                        if (is_file($new_path)) {
                            $files[$id . '/' . $file] = $new_path;
                        }
                    }
                }
            }

            // Ensure destination folder exists
            if (!file_exists($dest_folder)) {
                mkdir($dest_folder, 0777, true);
            }

            // Define the zip file name
            $zipname = $dest_folder . '/file.zip';
            $zip = new ZipArchive;

            if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
                // Create a new spreadsheet
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Controls Report');

                // Set default font
                $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(12);

                // Add header row
                $headers = [
                    'Control Name',
                    'Control Number',
                    'Control Type',
                    'Control Risk',
                    'Control Description',
                    'Frequency',
                    'Process Owner',
                    'Department',
                    'Due Date',
                    'Status'
                ];
                if (config('app.show_completed_by')) {
                    $headers[] = 'Completed By';
                }

                $sheet->fromArray($headers, NULL, 'A1');

                // Fetch controls from the database (Assuming you have appropriate models and relationships)
                $controls = Control::with([
                    'controlType',
                    'controlRisk',
                    'frequency',
                    'user',
                    'department',
                    'status',
                    'controlCompletion.user'
                ])->whereIn('id', $ids)->get();

                $row = 2; // Start from row 2 (below header)

                foreach ($controls as $control) {
                    $data = [
                        $control->control_name,
                        $control->control_number,
                        $control->controlType->controltype,
                        $control->controlRisk->controlrisk,
                        $control->control_description,
                        $control->frequency->name,
                        $control->user->full_name,
                        $control->department->name,
                        $control->due_date ? $control->due_date->format('Y-m-d') : 'Never Due',
                        $control->status->status_title
                    ];

                    if (config('app.show_completed_by')) {
                        $data[] = $control->controlCompletion->user->full_name ?? '';
                    }

                    $sheet->fromArray($data, NULL, 'A' . $row++);
                }

                // Save Excel to the destination folder
                $excelFileName = "ControlsReports_" . date("F-j-Y") . ".xlsx";
                $excelFilePath = $dest_folder . '/' . $excelFileName;
                $writer = new Xlsx($spreadsheet);
                $writer->save($excelFilePath);

                // Add the Excel file and attached files to the zip
                $saved_files[$id . '/' . $excelFileName] = $excelFilePath;

                if (!empty($saved_files) && !empty($files)) {
                    foreach (array_merge($saved_files, $files) as $key => $val) {
                        $zip->addFile($val, $key);
                    }
                } elseif (empty($files)) {
                    $zip->addFile($excelFilePath, $excelFileName);
                }

                $zip->close();
            }
        }

        // Prepare the zip file for download
        return response()->download($zipname)->deleteFileAfterSend(true);
    }
    function sortBackupUsers(Request $request)
    {
        $backupUsers = [];

        // Loop to gather non-empty backup users
        for ($i = 0; $i < 5; $i++) {
            $input_name = 'backup_user' . $i . '_id';
            if ($request->input($input_name) != '') {
                $backupUsers[] = $request->input($input_name);
            }
        }

        // Update the request data with sorted backup users
        for ($j = 0; $j < 5; $j++) {
            $input_name = 'backup_user' . $j . '_id';
            $request->merge([
                $input_name => isset($backupUsers[$j]) ? $backupUsers[$j] : ''
            ]);
        }
    }
    public function handleNeverDueAddEdit(&$control)
    {
        // Get the request data
        $data = request()->input('control');

        // Default occurrence value
        $occurrence = ['occurrence' => 1];

        // Check if this is an edit action
        if (isset($data['is_edit']) && $data['is_edit']) {
            // Delete all incomplete occurrences for this control
            ControlOccurrence::where('control_id', $data['id'])
                ->where('status_id', '!=', STATUS_COMPLETE)
                ->delete();

            // Get the latest occurrence for the control
            $latest = ControlOccurrence::where('control_id', $data['id'])
                ->orderBy('created_at', 'desc')
                ->first();

            // If a previous occurrence exists, increment its occurrence number
            if ($latest) {
                $occurrence['occurrence'] = $latest->occurrence + 1;
            }
        }

        // Ensure user_id is set
        if (empty($data['user_id'])) {
            return false;
        }

        // Prepare the occurrence data
        $occurrence['user_id'] = $data['user_id'];
        $occurrence['control_id'] = $data['id'];

        // Create a new control occurrence
        $newOccurrence = new ControlOccurrence();
        $newOccurrence->fill($occurrence);

        // Save the new occurrence
        if ($newOccurrence->save()) {
            // Update occurrences count on the control itself
            if (isset($occurrence['occurrence'])) {
                $control->occurrences = $occurrence['occurrence'];
                $control->save();
            }
        }

        return true;
    }

    public function handleRepetitionsOnAddEdit($data)
    {
        $day_of_week = false;


        if (!$data['user_id']) {
            return false;
        }

        $occurrence = $data;
        $occurrence['user_id'] = $data['user_id'];
        $occurrence['control_id'] = $data['id'];
        unset($occurrence['id']); // unset the "control" ID

        $this->_handleUserChange($data);

        if ($this->isNotRepeating($data)) {
            return;
        }

        if ($this->isToNotRepeating($data)) {
            $this->_handleToNotRepeating($data);
            return;
        }

        $is_to_repeating = $this->isToRepeating($data);
        $occurrence_changed = $this->occurrencesChanged($data);
        $frequency_changed = $this->frequencyChanged($data);
        $due_date_changed = $this->duedateChanged($data);
        $repeat_month_by_changed = $this->repeatMonthByChanged($data);

        $frequencyName = Frequency::find($data['frequency_id'])->name;

        if (isset($data['occurrences']) && $data['occurrences'] > 0) {
            $data['occurrences'] = 1; // create single occurrence by default;
        }

        $frequency_id = $data['frequency_id'];
        $occurrence['frequency_id'] = $data['frequency_id'];
        $frequency = Frequency::find($data['frequency_id']);

        if (isset($data['repeat_month_by']) && $data['repeat_month_by']) {
            $day_of_week = $data['repeat_month_by'];
        }

        if ($frequencyName == "Monthly" && $repeat_month_by_changed) {
            ControlOccurrence::where('control_id', $data['id'])
                ->where('status_id', '!=', STATUS_COMPLETE)
                ->delete();

            if ($data['repeat_month_by'] == 'day_of_month') {
                $day_of_week = 'day_of_month';
                $frequency_changed = true;
            } elseif ($data['repeat_month_by'] == 'day_of_week') {
                $day_of_week = 'day_of_week';
                $frequency_changed = true;
            }
        }

        if (!$is_to_repeating) {
            if ($data['repeat_ends_on'] == 'until' && $data['end_date'] != null && isset($data['occurrences'])) {
                $occurrence_changed = $data['occurrences'] != $data['occurrences'];
            }

            if ($frequency_changed) {
                $controller->email_message['list'][] = ('Control frequency has been changed!');
                ControlOccurrence::where('control_id', $data['id'])
                    ->where('status_id', '!=', STATUS_COMPLETE)
                    ->delete();

                $latest = ControlOccurrence::where('control_id', $occurrence['control_id'])
                    ->latest('due_date')
                    ->first();

                $next_date = $this->get_next_date($latest->due_date, $frequency, $day_of_week);
                $due_date_adjs = $this->getDueDateAdjustment($next_date, $frequency);
                $occurrence['due_date'] = $due_date_adjs[0];
                $occurrence['adjustment'] = $due_date_adjs[1];

                if ($occurrence['due_date'] < now()) {
                    $occurrence['status_id'] = STATUS_OVERDUE;
                }
            } elseif ($occurrence_changed) {
                if ($data['occurrences'] > $data['occurrences']) {
                    $addnumber = $data['occurrences'] - $data['occurrences'];
                    $_occurrence = $data['occurrences'] + 1;

                    $latest = ControlOccurrence::where('control_id', $data['id'])
                        ->latest('due_date')
                        ->first();

                    $occurrence['due_date'] = $latest->due_date;
                    $occurrence['reminder_date'] = $latest->reminder_date;
                    $occurrence['rem_post_date'] = $latest->rem_post_date;

                    $occurrence = $this->copyFieldsToArray($occurrence);

                    for ($x = 0; $x < $addnumber; $x++) {
                        if (isset($occurrence['adjustment']) && $occurrence['adjustment']) {
                            $occurrence['due_date'] = date('Y-m-d', strtotime($occurrence['due_date'] . ' -' . $occurrence['adjustment'] . ' days'));
                        }

                        $next_date = $this->get_next_date($occurrence['due_date'], $frequency, $day_of_week);
                        $due_date_adjs = $this->getDueDateAdjustment($next_date, $frequency);
                        $occurrence['due_date'] = $due_date_adjs[0];
                        $occurrence['adjustment'] = $due_date_adjs[1];

                        $occurrence['reminder_date'] = $this->get_next_date($occurrence['reminder_date'], $frequency, $day_of_week);
                        $occurrence['rem_post_date'] = $this->get_next_date($occurrence['rem_post_date'], $frequency, $day_of_week);

                        $occurrence['occurrence'] = $_occurrence++;
                        $occurrence['user_id'] = $data['user_id'];

                        ControlOccurrence::create($occurrence)->save();
                    }
                } else {
                    if ($data['occurrences'] == 0) {
                        // Handle non-recurring case
                    } elseif ($data['repeats']) {
                        $reducenumber = $data['occurrences'] - $data['occurrences'];
                        ControlOccurrence::where('control_id', $data['id'])
                            ->where('due_date', '>=', now())
                            ->where('status_id', '!=', STATUS_COMPLETE)
                            ->orderBy('due_date', 'desc')
                            ->limit($reducenumber)
                            ->delete();
                    }
                }

                $controller->email_message['list'][] = ('Control occurrence has been changed!');
            }

            if ($due_date_changed && !$frequency_changed) {
                $old_due_date = $data['due_date'];
                $new_due_date = $data['due_date'];
                $days = (strtotime($new_due_date) - strtotime($old_due_date)) / (60 * 60 * 24);

                ControlOccurrence::where('control_id', $data['id'])
                    ->where('status_id', '!=', STATUS_COMPLETE)
                    ->update([
                        'due_date' => DB::raw("DATE_ADD(due_date, INTERVAL {$days} DAY)"),
                        'rem_post_date' => DB::raw("DATE_ADD(rem_post_date, INTERVAL {$days} DAY)"),
                        'status_id' => $data['status_id']
                    ]);
            }
        }

        if ($frequency_changed || $is_to_repeating) {
            // Logic for adding new occurrences or repeating controls
        }
    }

    // Define additional helper functions (like isNotRepeating, get_next_date, etc.) here


    function _handleUserChange($control)
    {
        // We track the change in process owner
        if (isset($control['user_id'])) {
            // If process owner was changed, delete the old user from controls_assignees
            if ($control['user_id'] != $control['user_id']) {
                ControlOccurrence::where('control_id', $control['id'])
                    ->where('status_id', '!=', STATUS_COMPLETE)
                    ->update(['user_id' => $control['user_id']]);

                // More actions like setting emails, etc. here
            }
        }
    }

    function isNotRepeating($data)
    {
        if (isset($data['repeats'])) {
            // If the repetition has not been changed
            if ($data['repeats'] == false && $data['repeats'] == false) {
                return true;
            }
            return false;
        }
    }

    function isToRepeating($control)
    {
        if (isset($control['repeats'])) {
            // If the repetition has been changed
            if ($control['repeats'] == false && $control['repeats'] == true) {
                return true;
            }
            return false;
        }
    }

    function occurrencesChanged($control)
    {
        if (isset($control['occurrences_change'])) {
            // If the occurrences have been changed
            if ($control['occurrences_change'] == true) {
                return true;
            }
            return false;
        }
    }

    function frequencyChanged($control)
    {
        if (isset($control['frequency_change'])) {
            // If the frequency has been changed
            if ($control['frequency_change'] == true) {
                return true;
            }
            return false;
        }
    }

    function duedateChanged($control)
    {
        if (isset($control['due_date_change'])) {
            // If the due date has been changed
            if ($control['due_date_change'] == true) {
                return true;
            }
            return false;
        }
    }

    function repeatMonthByChanged($control)
    {
        if (isset($control['repeat_month_by_change'])) {
            // If the repeat month-by option has been changed
            if ($control['repeat_month_by_change'] == true) {
                return true;
            }
            return false;
        }
    }


    function isToNotRepeating($control)
    {
        if (isset($control['repeats'])) {
            //if the repetition has been changed
            if ($control['repeats'] == 1 && $control['repeats'] == 0) {
                return true;
            }
        }
    }

    /**
     * isToRepeating - Checks whether a control being changed to Repeating from NotRepeating
     * @param array $control
     * @return boolean/void
     */
    function doControlEmail($data)
    {
    
        $to = [];
        $backup_user = false;
        $user_to = $to[0] = User::find($data['user_id'])->email;

        // Set recursive level if needed (this may depend on your model structure)
        $control = Control::with(['frequency', 'backupUsers'])->find($data['id']);

        // Determine frequency
        $frequency = $control->frequency->name ?? 'No Frequency Set';

        // Get backup users' emails
        $backup_to = getBackupUsersEmails($data);

        if ($to) {
            if (in_array(request()->route()->getActionMethod(), ['edit', 'admin_edit']) && canEmail()) {
                // Get control data for email
                $controlData = $control->toArray();

                // Create ICS file attachment
                $icsAttachment = getICSattachment($controlData);

                // Notify un-assigned and assigned users on control change
                if (isset($data['user_id']) && $data['Control']['user_id'] != $data['user_id']) {
                    // Notify unassigned user
                    $to_unassign = User::find($data['user_id'])->email;
                    if (!empty($to_unassign)) {
                        Mail::to($to_unassign)->send(new ControlEmail('Control un-assigned!', 'control-unassigned', [
                            'control' => $controlData,
                            'due_date_formatted' => dateFormatted($data['due_date']),
                            'auth' => auth()->user(),
                        ]));
                    }

                    // Notify the newly assigned user
                    Mail::to(User::find($data['user_id'])->email)->send(new ControlEmail('Control assigned to you!', 'control-assigned', [
                        'control' => $controlData,
                        'due_date_formatted' => dateFormatted($data['due_date']),
                        'backup_user' => false,
                        'frequency' => $frequency,
                    ], $icsAttachment));
                }

                // Notify backup users on edit
                $edit_backup_To = getBackupUsersEmailsOnEdit($data);
                if (!empty($edit_backup_To['added'])) {
                    if (config('system.backup_process_owner_email') == 'all') {
                        Mail::to($edit_backup_To['added'])->send(new ControlEmail('You are a backup process owner to a new Control', 'control-add-backup-user', [
                            'control' => $controlData,
                            'due_date_formatted' => dateFormatted($control->due_date),
                            'frequency' => $frequency,
                        ], $icsAttachment));
                    }
                }

                if (!empty($edit_backup_To['removed'])) {
                    Mail::to($edit_backup_To['removed'])->send(new ControlEmail('You are removed as backup process owner from a Control', 'control-remove-backup-user', [
                        'control' => $controlData,
                        'due_date_formatted' => dateFormatted($control->due_date),
                        'frequency' => $frequency,
                    ]));
                }

                if (canEmail()) {
                    // Control updated email
                    Mail::to($to)->send(new ControlEmail('Control was Updated!', 'control-edit', [
                        'control' => $controlData,
                        'due_date_formatted' => dateFormatted($data['Control']['due_date']),
                        'frequency' => $frequency,
                    ]));
                }

                // Unlink ICS attachment
                unlink($icsAttachment);
            } else if (in_array(request()->route()->getActionMethod(), ['add', 'admin_add', 'import', 'admin_import'])) {
                // Handle 'add' or 'import' actions
                $control->id = $data['Control']['id'];
                $controlData = $control->toArray();

                $icsAttachment = getICSattachment($controlData);

                // Notify control owner
                Mail::to($to[0])->send(new ControlEmail('Control was added & assigned to you!', 'control-add', [
                    'control' => $controlData,
                    'due_date_formatted' => dateFormatted($control->due_date),
                    'frequency' => $frequency,
                ], $icsAttachment));

                // Notify control creator
                $creator_to = User::find($data['Control']['creator_id'])->email;
                if ($creator_to) {
                    Mail::to($creator_to)->send(new ControlEmail('Control was added by you!', 'control-add', [
                        'control' => $controlData,
                        'due_date_formatted' => dateFormatted($control->due_date),
                        'frequency' => $frequency,
                    ], $icsAttachment));
                }

                // Notify backup users
                if (config('system.backup_process_owner_email') == 'all' && !empty($backup_to)) {
                    Mail::to($backup_to)->send(new ControlEmail('Control was added & assigned to you as backup Process Owner!', 'control-add-backup-user', [
                        'control' => $controlData,
                        'due_date_formatted' => dateFormatted($control->due_date),
                        'frequency' => $frequency,
                    ], $icsAttachment));
                }

                unlink($icsAttachment);
            }
        }
    }

    // Helper functions

}
