<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormAnswers;
use App\Models\Test;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(Test::getUserTests());
    }

    public function indexView(): Response
    {
        $token = csrf_token();
        return Inertia::render('Tests/Index', ['token' => $token]);
    }


    public function startTest(Request $request, int $testId)
    {
        $data = json_decode($request->input('data')); //parse data
        $test = Form::findOrFail($testId);
//        dd($data);
        if ($data->pending === 0) {
            return response('Ya has contestado esta evaluación', 401);
        }

        return Inertia::render('Tests/Show', ['test' => $test, 'functionary' => ['name' => $data->name, 'id' => $data->evaluated_id], 'role' => $data->role, 'canSend' => true]);
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
        $form = Form::findOrFail($request->input('form_id'));
        FormAnswer::createFormFromRequest($request, $form);
        return response()->json(['messages' => 'Formulario diligenciado exitosamente. Serás redirigido a la página de inicio']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
