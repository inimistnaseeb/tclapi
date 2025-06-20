<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskType;
use App\Models\Client;
use App\Models\Frequency;
use App\Models\TaskOccurrence;
use App\Models\TaskProcessOwner;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request, $parent_id = null)
    {
        // Load necessary models if needed
        $task = new Task();

        // Validate incoming request data
        // $request->validate([
        //     'task.due_date' => 'required|date',
        //     'task.status_id' => 'required|integer',
        //     'task.repeats' => 'nullable|boolean',
        //     'task.repeat_ends_on' => 'nullable|string',
        //     'Selected_TaskProcessOwner.user_id' => 'nullable|array',
        // ]);

        // Handle Task data
        $data = $request->input('Task');
        $data['first_due_date'] = $data['due_date'];

        // Check if the task repeats
        if (!$data['repeats']) {
            $data['occurrences'] = 0;
        } elseif ($data['repeats'] && $data['repeat_ends_on'] == 'until') {
            $data['occurrences'] = 1;
        }

        // Check and set status based on the due date
        $dueDate = $data['due_date'];
        if (!empty($dueDate)) {
            if (strtotime($dueDate) > strtotime(date('Y-m-d')) && $data['status_id'] == STATUS_OVERDUE) {
                $data['status_id'] = STATUS_INPROCESS;
            } elseif (strtotime($dueDate) < strtotime(date('Y-m-d'))) {
                $data['status_id'] = STATUS_OVERDUE;
            } else {
                $data['status_id'] = STATUS_INPROCESS;
            }
        } else {
            $data['status_id'] = STATUS_NOT_STARTED;
        }

        // Handle TaskTaskType data if it exists
        if (!empty($request->input('TaskTaskType.task_type_id'))) {
            $data['TaskTaskType'] = Task::formatHasManyForSaveAll(
                $request->input('TaskTaskType.task_type_id'),
                'task_type_id'
            );
        }

        // Handle Selected_TaskProcessOwner data
        if (!empty($request->input('Selected_TaskProcessOwner.user_id'))) {
            $data['TaskProcessOwner'] = Task::formatHasManyForSaveAll(
                $request->input('Selected_TaskProcessOwner.user_id'),
                'user_id'
            );

            // Update TaskProcessOwner status_id
            $data['TaskProcessOwner'] = Task::setstatusHasManyForSaveAll(
                $data['TaskProcessOwner'],
                ['status_id' => $data['status_id']]
            );
        }

        // Sort Backup Users if needed (custom function)
        $this->_sortBackupUsers();


        // Create the task
        $task->fill($data);
        $task->save();

        // Handle file uploads if any (using Laravel's file upload mechanism)
        if ($request->hasFile('task_files')) {
            foreach ($request->file('task_files') as $file) {
                $file->store('tasks'); // Store in 'tasks' directory
            }
        }

        // Handle repetitions and additional logic
        $this->handleRepetitionsOnAddEdit($task);

        // Send email about the task
        $this->__doTaskEmail($task);

        // Update parent task if it's a sub-task
        if ($parent_id || $request->input('task.parent_id')) {
            $parent_id = $parent_id ?? $request->input('task.parent_id');
            Task::updateSubTaskCount($parent_id);
        }

        // Flash success message and redirect to index
        session()->flash('success', 'Task saved successfully.');
        return response()->json('success');
    }

    // Custom helper methods can be placed in a relevant service class, for instance:
    private function _sortBackupUsers()
    {
        // Sorting backup users logic here
    }

    public function handleRepetitionsOnAddEdit($taskData)
    {
       

        $this->manageOccurrenceUsers($taskData);

        if (!empty($request->input('selected_task_process_owner.user_id'))) {
            $taskProcessOwner = $request->input('selected_task_process_owner.user_id');
        }

        // If no user is assigned, return false
        if (empty($taskProcessOwner)) {
            return false;
        }

        $taskRepetition = $taskData;
        $taskRepetition['task_id'] = $taskData['id'];
        unset($taskRepetition['id']);

        if ($this->isNotRepeating($taskData)) {
            return;
        }

        // Handle tasks that were repeating but now are not
        if ($this->isToNotRepeating($taskData)) {
            $this->handleToNotRepeating($taskData);
            return;
        }

        // Determine what changed in the task
        $isToRepeating = $this->isToRepeating($taskData);
        $occurrenceChanged = $this->occurrencesChanged($taskData);
        $frequencyChanged = $this->frequencyChanged($taskData);
        $dueDateChanged = $this->dueDateChanged($taskData);
        $repeatMonthByChanged = $this->repeatMonthByChanged($taskData);

        // Get the frequency name
        $frequencyName = Frequency::find($taskData['frequency_id'])->name;
        $taskRepetition['frequency_id'] = $taskData['frequency_id'];

        // Handle monthly repetition changes
        if ($frequencyName == "Monthly" && $repeatMonthByChanged) {
            TaskOccurrence::where('task_id', $taskData['id'])
                ->where('status_id', '!=', STATUS_COMPLETE)
                ->delete();

            if (isset($taskData['repeat_month_by'])) {
                if ($taskData['repeat_month_by'] == 'day_of_month') {
                    $dayOfWeek = 'day_of_month';
                    $frequencyChanged = true;
                } elseif ($taskData['repeat_month_by'] == 'day_of_week') {
                    $dayOfWeek = 'day_of_week';
                    $frequencyChanged = true;
                }
            }
        }

        // Handle number of occurrences
        if ($taskData['repeat_ends_on'] === 'until' && $taskData['end_date'] !== null) {
            $taskData['occurrences'] = 1;
            $occurrenceDueDate = $this->getNextDate($taskRepetition['due_date'], $frequencyName, $dayOfWeek);
            $endDate = $taskData['end_date'];

            while ($occurrenceDueDate <= $endDate && $taskData['occurrences'] < 5) {
                $occurrenceDueDate = $this->getNextDate($occurrenceDueDate, $frequencyName, $dayOfWeek);
                $taskData['occurrences']++;
            }
            Task::where('id', $taskData['id'])->update(['occurrences' => $taskData['occurrences']]);
        }

        // Update the task occurrences based on changes
        if (!$isToRepeating) {
            if ($taskData['repeat_ends_on'] == 'until' && isset($taskData['__occurrences'])) {
                $occurrenceChanged = $taskData['occurrences'] != $taskData['__occurrences'];
            }

            // Frequency changed - delete old repetitions
            if ($frequencyChanged) {
                TaskOccurrence::where('task_id', $taskData['id'])
                    ->where('status_id', '!=', STATUS_COMPLETE)
                    ->delete();
            } elseif ($occurrenceChanged) {
                $this->updateOccurrences($taskData, $taskRepetition, $frequencyName, $dayOfWeek);
            }

            if ($dueDateChanged && !$frequencyChanged) {
                $this->updateDueDates($taskData);
            }
        }
    }

    private function updateOccurrences($taskData, &$taskRepetition, $frequency, $dayOfWeek)
    {
        if ($taskData['occurrences'] > $taskData['__occurrences']) {
            $addNumber = $taskData['occurrences'] - $taskData['__occurrences'];
            $latestTask = TaskOccurrence::where('task_id', $taskData['id'])->latest('due_date')->first();

            if ($latestTask) {
                $taskRepetition['due_date'] = $latestTask->due_date;
            } else {
                $taskRepetition['due_date'] = $taskData['due_date'];
            }

            for ($x = 0; $x < $addNumber; $x++) {
                $nextDate = $this->getNextDate($taskRepetition['due_date'], $frequency, $dayOfWeek);
                $taskRepetition['due_date'] = $this->adjustDueDate($nextDate, $frequency);

                TaskOccurrence::create($taskRepetition);
            }
        }
    }

    private function updateDueDates($taskData)
    {
        $oldDueDate = $taskData['__due_date'];
        $newDueDate = $taskData['due_date'];
        $daysDifference = (strtotime($newDueDate) - strtotime($oldDueDate)) / (60 * 60 * 24);

        TaskOccurrence::where('task_id', $taskData['id'])
            ->where('status_id', '!=', STATUS_COMPLETE)
            ->update([
                'due_date' => DB::raw("DATE_ADD(due_date, INTERVAL $daysDifference DAY)")
            ]);
    }

    private function getNextDate($currentDate, $frequency, $dayOfWeek)
    {
        // Logic to calculate the next due date based on frequency and day of the week
        return $nextDate;
    }

    private function adjustDueDate($date, $frequency)
    {
        // Logic to adjust due date based on task conditions
        return [$adjustedDate, $adjustment];
    }

    private function __doTaskEmail($task)
    {
        // Task email logic here
    }
}
