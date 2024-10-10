<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\FunctionaryProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ExternalClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(User::where('is_external_client','=', 1)->select(['*', 'id as user_id'])->get());
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
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $passwordToExternalClient = Str::random(12);
        $externalClientRoleId = Role::getRoleIdByName('cliente externo');

        $user = User::create([
            'name' => $request->input(['name']),
            'email' => $request->input(['email']),
            'is_external_client' => true,
            'password' => Hash::make($passwordToExternalClient),
        ]);

        DB::table('role_user')->updateOrInsert(
            ['user_id' => $user['id'],
                'role_id' => $externalClientRoleId]
        );

        $data = ['name' => $request->input(['name']),'email' => $request->input(['email']), 'password' => $passwordToExternalClient];

        $email = new \App\Mail\ExternalClientCreated($data);

        Mail::bcc([$request->input(['email'])])->send($email);
        return response()->json(['message' => 'Cliente externo creado exitosamente, al correo ingresado se han enviado las credenciales de acceso para EDA']);
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
     * @param Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $externalClient)
    {
        $externalClient->update($request->all());
        return response()->json(['message' => 'Información actualizada exitosamente']);
    }

    public function updatePassword(Request $request)
    {
        $newPasswordToExternalClient = Str::random(12);
        $user = User::UpdateOrCreate(
            ['id' => $request->input(['id'])],
            ['password' => Hash::make($newPasswordToExternalClient)]);

        $data = ['name' => $request->input(['name']),'email' => $request->input(['email']), 'password' => $newPasswordToExternalClient];
        $email = new \App\Mail\ExternalClientNewPassword($data);
        Mail::bcc([$user['email']])->send($email);

        return response()->json(['message' => 'Contraseña actualizada exitosamente']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $externalClient)
    {
            $externalClientAssignments = DB::table('assessments')->where('evaluator_id','=', $externalClient['id'])->get();

            if(count($externalClientAssignments) === 0){
                //The external client has no assignments, so we can delete all the registers associated to it.
                DB::table('role_user')->where('user_id','=',$externalClient['id'])->delete();
                try {
                    $externalClient->delete();
                } catch (QueryException $e) {
                    return response()->json(['message' => 'Ha ocurrido un error al intentar borrar al cliente externo'], 400);
                }
                return response()->json(['message' => 'Cliente externo eliminado exitosamente']);
            }

            return response()->json(['message' => 'No puedes eliminar a un cliente externo si ya tiene alguna asignación para evaluar a otro funcionario'], 400);

    }
}
