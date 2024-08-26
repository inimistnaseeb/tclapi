<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\QuestionType;
use App\Models\Quiz;
use Illuminate\Http\Request;
use App\Models\QuizAttempt;
use App\Models\QuizSlot;
use Illuminate\Support\Facades\Validator;

class QuizAttemptController extends Controller
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
        return response()->json('hello');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeOld(Request $request)
    {
        $user_id = $request->user_id ?? null;
        $quiz_id = $request->quiz_id ?? null;

        if (empty($user_id) || empty($quiz_id)) {
            return response()->json('User ID and Quiz ID are required.', 400);
        }

        $total_questions = QuizSlot::where('quiz_id', $quiz_id)->count();

        $correctQuestions = 0; //To get the total numbers of correct questions user answer
        if ($quizAttemptData = QuizAttempt::with('QuestionAttempt')->get()->toArray()) {
            foreach ($quizAttemptData as $quizAttempt) {
                foreach ($quizAttempt['question_attempt'] as $questionAttempt) {
                    if ($questionAttempt['rightanswer'] === $questionAttempt['responsesummary']) {
                        $correctQuestions += 1;
                    }
                }
            }
        }

        $timefinished = now();
        $earned_grade =  $total_questions !== 0 ? ($correctQuestions / $total_questions) * 10 : null;
        $result =  $earned_grade !== null && $earned_grade > 3.3 ? 'pass' : 'fail';

        $quizAttempt = QuizAttempt::where('quiz_id', $quiz_id)
            ->where('user_id', $user_id)
            ->where('state', 'inprogress')
            ->first();

        if ($quizAttempt) {
            $quizAttempt->attempt += 1;
            $quizAttempt->timefinished = $timefinished;
            $quizAttempt->earned_grade = $earned_grade;
            $quizAttempt->correctquestions = $correctQuestions;
            $quizAttempt->totalquestions =  $total_questions;
            $quizAttempt->state =  $result === 'pass' ? 'finished' : 'inprogress';
            $quizAttempt->result =   $result === 'pass' > 0 ? $result : null;
            $quizAttempt->save();
            return response()->json('updated');
        } else {
            $input = [
                'user_id' => $user_id,
                'quiz_id' => $quiz_id,
                'attempt' => 1,
                'timestart' => now(),
                'timefinished' => $timefinished,
                'correctquestions' => $correctQuestions,
                'state' =>  $result === 'pass' ? 'finished' : 'inprogress',
                'totalquestions' => $total_questions,
                'earned_grade' => $earned_grade,
                'result' =>  $result === 'pass' > 0 ? $result : null,

            ];

            if (QuizAttempt::create($input)) {
                return response()->json('success');
            } else {
                return response()->json('failed');
            }
        }
    }

    public function store(Request $request)
    {
        $quiz_id = $request->quiz_id ?? null;
        $quiz = Quiz::findOrFail($quiz_id);
        $total_questions = QuizSlot::where('quiz_id', $quiz_id)->count();
        $timefinished = now();
        $correctQuestions = 0; // To get the total number of correct questions user answers

        if ($request->questionAttempt) {
            foreach ($request->questionAttempt as $ques_id => $value) {
                if (!empty($value)) {
                    $question = Question::with('questionAnswers', 'questionType')->findOrFail($ques_id)->toArray();

                    if ($question['question_type']['qtype'] === 'multiplechoice-one' && $value === $question['question_answers']['correct_answer']) {
                        $correctQuestions += 1;
                    } elseif ($question['question_type']['qtype'] === 'truefalse' && $value) {
                        $correctQuestions += 1;
                    } elseif ($question['question_type']['qtype'] === 'multiplechoice-multi') {
                        $correctAswer  = explode(",", $question['question_answers']['correct_answer']);
                        $bol = [];

                        foreach ($value as $val) {
                            if (in_array($val, $correctAswer)) {
                                $bol[] = 'right';
                            } else {
                                $bol[] = 'wrong';
                            }
                        }
                        if (!in_array("wrong", $bol)) {
                            $correctQuestions += 1;
                        }
                    }
                }
            }
        }

        // echo "<pre>";print_r($request->input());die;
        $earned_grade = $total_questions !== 0 ? ($correctQuestions / $total_questions) * 100 : null;
        $min_pass_percentage = $quiz->minpassquestions; // Assuming minpassquestions contains the percentage

        // Check if earned grade is not null and greater than or equal to the minimum pass percentage
        $result = $earned_grade !== null && $earned_grade >= $min_pass_percentage ? 'pass' : 'fail';

        $quizAttempt = QuizAttempt::where('quiz_id', $quiz_id)
            ->where('user_id', $request->user_id)
            ->where('state', 'inprogress')
            ->first();

        if (!$quizAttempt) {
            $quizAttempt = QuizAttempt::create([
                'user_id' => $request->user_id,
                'quiz_id' => $quiz_id,
                'attempt' => 1,
                'timestart' => now(),
                'state' =>  $result === 'pass' ? 'finished' : 'inprogress',
                'totalquestions' => $total_questions,
                'earned_grade' => $earned_grade,
                'result' =>  $result,
            ]);
        }

        $questionAttemptData = [];
        $questionAttempt = $request->input('questionAttempt');

        if ($questionAttempt !== null && sizeof($questionAttempt)) {
            foreach ($questionAttempt as $ques_id => $value) {
                if (!empty($value)) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $question = Question::with('questionAnswers', 'questionType')->findOrFail($ques_id);

                    $questionSummary = $question->title . ':' . $question->questionAnswers->answer_options;
                    $rightAnswer = $question->questionAnswers->correct_answer;

                    $existingQuestionAttempt = QuestionAttempt::where('question_id', $ques_id)
                        ->where('quiz_attempt_id', $quizAttempt->id)
                        ->first();

                    if ($existingQuestionAttempt) {
                        $existingQuestionAttempt->questionSummary = $questionSummary;
                        $existingQuestionAttempt->rightAnswer = $rightAnswer;
                        $existingQuestionAttempt->responsesummary = $value;
                        $existingQuestionAttempt->save();
                    } else {
                        $questionAttemptData[] = [
                            'quiz_attempt_id' => $quizAttempt->id,
                            'question_id' => $ques_id,
                            'questionSummary' => $questionSummary,
                            'rightAnswer' => $rightAnswer,
                            'responsesummary' => $value,
                        ];
                    }
                }
            }


            if (!empty($questionAttemptData)) {
                QuestionAttempt::insert($questionAttemptData);
            }
            $quizAttempt->attempt += 1;
            $quizAttempt->timefinished = $timefinished;
            $quizAttempt->earned_grade = $earned_grade;
            $quizAttempt->correctquestions = $correctQuestions;
            $quizAttempt->totalquestions = $total_questions;
            $quizAttempt->state =  $result === 'pass' ? 'finished' : 'inprogress';
            $quizAttempt->result =  $result;
            $quizAttempt->save();
        }
        return response()->json($quizAttempt);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            return response()->json(QuizAttempt::with('QuestionAttempt', 'QuestionAttempt.Question')->find($id));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
