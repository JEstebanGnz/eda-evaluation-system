<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Collection
     */
    public function index(Request $request)
    {
            $commitmentId = $request['id'];
            return DB::table('certifications as c')
                ->select(['c.id', 'c.original_file_name', 'c.encoded_file_name', 'c.commitment_id', 'c.updated_at', 'u.name as user_name'])
                ->where('c.commitment_id', '=', $commitmentId)
                ->join('users as u', 'c.added_by','=', 'u.id')->get();

    }


    public function downloadFile($certification)
    {

        //Get the file info
        $certification = Certification::where('encoded_file_name','=', $certification)->first();

        //Once the file is retrieved, then just download it
        try{
            return Storage::disk('local')->download($certification['encoded_file_name'], $certification['original_file_name']);
        }catch (\Exception $exception){
            return response()->json(['message' => 'No se pudo descargar el archivo, comunícate con g3@unibague.edu.co'], 400);
        }


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
    public function store(Request $request): JsonResponse
    {
        if($request->hasFile('file')){
            $file = $request->file('file');
            $commitmentId = $request->input("commitment_id");
            $userId = auth()->user()->id;

            try{
                $savedFile = Storage::disk('local')->putFileAs('/', $file,
                    $file->hashName());
                Certification::create(['original_file_name' => $file->getClientOriginalName(),
                    'encoded_file_name' => $savedFile,
                    'commitment_id' => $commitmentId,
                    'added_by' => $userId]);
            } catch (JsonException $e){
                return response()->json(['message' => 'No se puedo cargar el archivo, inténtalo más tarde o comúnicate con g3@unibague.edu.co'], 400);
            }

            return response()->json(['message' => 'El archivo se ha añadido exitosamente al compromiso']);
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
    public function destroy(Certification $certification)
    {

        try{
            $certificationPath = $certification->encoded_file_name;
            $certification->delete();
            Storage::disk('local')->delete($certificationPath);
        } catch(\Exception $exception){
            return response()->json(['message' => 'No se pudo borrar el archivo, inténtalo más tarde o comúnicate con g3@unibague.edu.co'], 400);

        }
        return response()->json(['message' => 'El archivo se ha borrado exitosamente']);
    }
}
