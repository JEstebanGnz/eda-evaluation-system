<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(DB::table('reminders')->get());
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

        $reminder = $request->all();

        if($reminder['reminder_type'] === 'commitment' && $reminder['send_before'] === 'start'){
            return response()->json(['message' => 'No se puede configurar un recordatorio antes del comienzo de un compromiso'],400);
        }

        DB::table('reminders')->updateOrInsert(['reminder_type'=> $reminder['reminder_type'], 'send_before' => $reminder['send_before']],['days'=> $reminder['days']]);

        return response()->json(['message' => 'Recordatorio creado exitosamente']);
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
        $reminder = $request->all();
        if($reminder['reminder_type'] === 'commitment' && $reminder['send_before'] === 'start'){
            return response()->json(['message' => 'No se puede configurar un recordatorio antes del comienzo de un compromiso'],400);
        }
        DB::table('reminders')->updateOrInsert(['reminder_type'=> $reminder['reminder_type'], 'send_before' => $reminder['send_before']],['days'=> $reminder['days']]);

        return response()->json(['message' => 'Recordatorio actualizado exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($reminder)
    {
        DB::table('reminders')->where('id','=',$reminder)->delete();
        return response()->json(['message' => 'Recordatorio eliminado exitosamente']);


    }
}
