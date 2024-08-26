<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\QuestionAttempt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class QuestionAttemptController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'quiz_attempt_id' => 'required',
            'question_id' => 'required',
            'responsesummary' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }
        $question = Question::with('questionAnswers')->find($request->question_id);
        $questionSummary = $question->title . ':' . $question->questionAnswers->answer_options;
        $rightAnswer =  $question->questionAnswers->correct_answer;
        $newRequest = $request->merge(['questionsummary' => $questionSummary, 'rightanswer' => $rightAnswer]);
        $questionAttempt = new QuestionAttempt();
        if ($questionAttempt->fill($newRequest->all())->save()) {
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
    public function show(QuestionAttempt $questionAttempt, $id)
    {
        // $id used here is denoting the quiz_attempt table primary key
        try {
            $data = $questionAttempt->Where('quiz_attempt_id', $id)->get();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
