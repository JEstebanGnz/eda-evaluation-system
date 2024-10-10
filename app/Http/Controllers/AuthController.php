<?php

namespace App\Http\Controllers;

use App\Helpers\AtlanteProvider;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends Controller
{

    public function landing(Request $request)
    {
        return Inertia::render('Landing/Index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
        /*return redirect('/landing');*/
//           //
    }

    public function handleAuthRedirect(): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if ($user) {
            return redirect()->route('redirect');
        }
        return redirect()->route('landing.index.view');
/*        return redirect()->route('login');*/
    }

    public function handleRoleRedirect()
    {
        $user = auth()->user();
        if ($user->role()->name == "funcionario") {
            return Inertia::render('Tests/Index');
        }
        if ($user->role()->name == "administrador") {
            return Inertia::render('Users/Index');
        }
        if ($user->role()->name == "administrador de dependencia") {
            $dependencies = $this->adminHasMultipleDependencies($user);
            if(count($dependencies) > 1){
                return Inertia::render('Dependencies/LandingMultipleDependenciesAdmin', ['dependencies' => $dependencies]);
            }
            return Inertia::render('Dependencies/AssessmentStatus', ['dependency' => $dependencies[0]]);
        }
    }

    public function adminHasMultipleDependencies($user)
    {
        //Check in what dependencies is the user an admin
        $dependencyAdminRoleId = Role::getRoleIdByName($user->role()->name);
        $userDependencies = DB::table('dependency_user')->where('user_id', '=', $user['id'])
            ->where('role_id','=', $dependencyAdminRoleId)->get()->toArray();
        $userDependenciesId = array_unique(array_column($userDependencies, 'dependency_identifier'));
        return DB::table('dependencies')->whereIn('identifier', $userDependenciesId)->get();
    }

    public function externalClientLogin(Request $request)
    {
        //Error messages
        $messages = [
            "email.required" => "El correo es obligatorio",
            "email.email" => "Formato de correo incorrecto",
            "email.exists" => "No se ha encontrado un usuario asociado a la cuenta de correo ingresada",
            "password.required" => "Se requiere la contraseña para ingresar",
        ];

        // validate the form data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        } else {
            // attempt to log
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password ])) {
                // if successful -> redirect to roleRedirect
                return redirect()->route('tests.index.view');
            }

            // if unsuccessful -> redirect back
            return redirect()->back()->withInput($request->only('email'))->withErrors([
                'approve' => 'Contraseña incorrecta.',
            ]);
        }
    }


    public function originalExternalClientLogin(Request $request)
    {

        //Error messages
        $messages = [
            "email.required" => "El correo es obligatorio",
            "email.email" => "Formato de correo incorrecto",
            "email.exists" => "El correo ingresado no se encuentra en la base de datos",
            "password.required" => "Se requiere la contraseña para ingresar",
        ];

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('tests.index.view');
        }
        return back()->withErrors([
            'email' => 'Correo o contraseñas incorrectas, inténtelo nuevamente.',
        ]);
    }


    public function redirectGoogleLogin()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = $googleUser->email;
            $indexOfAtSymbol = stripos($email, "@", 0);
            $unibagueString = strrpos($email, "unibague", $indexOfAtSymbol);

            if ($unibagueString == false) {
                return response('Tu tipo de cuenta no es el adecuado para ingresar a esta plataforma', 403);
            }
        } catch (\Exception $e) {
            return redirect()->route('login');
        }

        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'password' => 'automatic_generate_password'
            ]);

            //Assign the default role (functionary)
            $role = Role::where('name', 'funcionario')->first();
            Role::assignRole($user->id, $role->id);
            session(['role' => $role->id]);
        }

        Auth::login($user);

        if ($user->hasOneRole()) {
            session(['role' => $user->roles[0]->id]);
            return redirect()->route('redirect');
        }

        return redirect()->route('pickRole');
    }

    public function pickRole()
    {
        return Inertia::render('Auth/PickRole');
    }




}
