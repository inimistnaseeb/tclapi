<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSlot;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Quiz::with('quizSlots.question.questionAnswers',  'quizSlots.question')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }
        $Quiz  = new Quiz;
        $Quiz->name = $request->name;
        $Quiz->description = $request->description;
        $Quiz->minpassquestions = $request->minpassquestions;
        if ($Quiz->save()) {
            return response()->json('success');
        } else {
            return response()->json('failed');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $quiz = Quiz::with('quizSlots.question.questionAnswers', 'quizSlots.question.questionType')->find($id);

        QuizAttempt::create([
            'quiz_id' => $id,
            'user_id' => 1,
            'attempt' => 1,
            'timestart' => now(),
            'state' => 'inprogress'
        ]);
        return response()->json($quiz);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $quiz = Quiz::with('quizSlots.question')->find($id);
        return response()->json($quiz);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $quiz = Quiz::findOrFail($id);
            $quiz->name = $request->input('name');
            $quiz->description = $request->input('description');
            $quiz->minpassquestions = $request->input('minpassquestions');
            $quiz->save();

            $question_ids = $request->input('question_ids', []);

            // Ensure $question_ids is an array
            if (!is_array($question_ids)) {
                // Assuming $question_ids is a comma-separated string, you can explode it
                $question_ids = explode(',', $question_ids);
            }

            $existing_question_ids = $quiz->quizSlots()->pluck('question_id')->toArray();
            $question_ids_to_add = array_diff($question_ids, $existing_question_ids);
            $question_ids_to_delete = array_diff($existing_question_ids, $question_ids);

            $slot = 1;
            foreach ($question_ids_to_add as $question_id) {
                $quizSlot = new QuizSlot;
                $quizSlot->quiz_id = $id;
                $quizSlot->slot = $slot;
                $quizSlot->question_id = $question_id;
                $quizSlot->save();
                $slot++;
            }


            if (!empty($question_ids_to_delete)) {
                $quiz->quizSlots()->where('quiz_id', $id)->whereIn('question_id', $question_ids_to_delete)->delete();
            }

            return response()->json('success', 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quiz $quiz)
    {
        try {
            $quiz->quizSlots()->delete();
            $quiz->delete();
            return response()->json('deleted');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json($errorMessage);
        }
    }



    //this function below is created to store the associated data the question selected to the particular quiz
    // public function quizSlot(Request $request)
    // {
    //     if (sizeof($request->all())) {
    //         $quizSlot = new QuizSlot;
    //         $input = $request->all();
    //         if ($quizSlot->fill($input)->save()) {
    //             return response()->json('success');
    //         } else {
    //             return response()->json('failed');
    //         }
    //     }
    // }
}
