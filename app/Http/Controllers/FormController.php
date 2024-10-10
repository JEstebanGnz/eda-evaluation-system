<?php

namespace App\Http\Controllers;

use App\Http\Requests\CopyFormRequest;
use App\Http\Requests\DestroyFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Form::getCurrentForms());
    }

    public function copy(CopyFormRequest $request, Form $form): JsonResponse
    {
        $newForm = $form->replicate(['name']);
        $newForm->name = 'Copia de ' . $form->name;
        $newForm->save();

        return response()->json(['message' => 'Formulario copiado exitosamente']);
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
    public function store(UpdateFormRequest $request): JsonResponse
    {
        Form::createForm($request);
        return response()->json(['message' => 'Formulario actualizado exitosamente']);
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
    public function destroy(DestroyFormRequest $request, Form $form): JsonResponse
    {
        if (count($form->formAnswers) !== 0) {
            return response()->json(['message' => 'No puedes borrar un formulario con respuestas'],400);
        }
        $form->delete();
        return response()->json(['message' => 'Formulario borrado exitosamente']);
    }

    public static function getWithoutQuestions(): JsonResponse
    {
        return response()->json(Form::withoutQuestions());
    }

}
