<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuestionAnswer;
use Illuminate\Support\Facades\Validator;


class QuestionAnswerController extends Controller
{
    public function index()
    {
    }

    public function create()
    {
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|unique:question_answers',
            'answer_options' => 'required',
            'correct_answer' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $questionAnswer = new QuestionAnswer;
        $questionAnswer->question_id = $request->question_id;
        $questionAnswer->answer_options = $request->answer_options;
        $questionAnswer->correct_answer = $request->correct_answer;

        if ($questionAnswer->save()) {
            return response()->json('success');
        } else {
            return response()->json('failed', 500);
        }
    }
}
