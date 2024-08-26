<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\Quiz;
use App\Models\QuizSlot;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $question = Question::with('questionAnswers', 'questionType')->get();
        return response()->json($question);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
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
            'title' => 'required|unique:questions',
            // 'description' => 'required',
            'question_type_id' => 'required',
            'answer_options' => 'required',
            'correct_answer' => 'required',
        ]);
        // return  $validator->errors();
        if ($validator->fails()) {
            return response()->json('true');
        }

        $question = Question::create([
            'title' => $request->title,
            'description' => $request->description,
            'question_type_id' => $request->question_type_id,
            'creator_id' => $request->creator_id,
        ]);

        if ($question) {
            $questionAnswer = new QuestionAnswer();
            $questionAnswer->question_id = $question->id;
            $questionAnswer->answer_options = implode(",", $request->answer_options);
            if (!is_array($request->correct_answer)) $questionAnswer->correct_answer = $request->correct_answer;
            else $questionAnswer->correct_answer = implode(",", $request->correct_answer);

            if ($questionAnswer->save()) {
                return response()->json('success');
            }
        }

        return response()->json('failed', 500);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $question = Question::with('questionAnswers')->find($id);
        return response()->json($question);
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
            'title' => 'required|unique:questions,title,' . $id,
            'description' => 'required',
            'question_type_id' => 'required',
            'answer_options' => 'required',
            'correct_answer' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }
        $question = Question::find($id);
        if ($question) {
            $question->title = $request->title;
            $question->description = $request->description;
            $question->question_type_id = $request->question_type_id;
            $question->creator_id = $request->creator_id;

            if ($question->save()) {
                $questionAnswer = QuestionAnswer::find($request->questionAnswerId); //this is the questionAnswer table primary key.
                $questionAnswer->answer_options = $request->answer_options;
                $questionAnswer->correct_answer = $request->correct_answer;

                if ($questionAnswer->save()) {
                    return response()->json('success');
                }
            }
        }
        return response()->json('failed', 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Question $question)
    {
        try {
            $question->questionAnswers()->delete();
            $question->quizSlots()->delete();
            $question->delete();
            return response()->json('deleted');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return response()->json($errorMessage);
        }
    }

    public function restoreAll()
    {
        Question::onlyTrashed()->restore();
        Quiz::onlyTrashed()->restore();
        QuestionAnswer::onlyTrashed()->restore();
        QuizSlot::onlyTrashed()->restore();
        return response()->json('restored successfully');
    }
}
