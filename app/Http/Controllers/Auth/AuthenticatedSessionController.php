<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        //return view('auth.login');
    }

     public function __construct()
    {
         // if(!file_exists(storage_path()."/installed")){
         //    header('location:install');
         //   die;
        
        // $this->middleware('guest')->except('logout');
        // $this->middleware('guest:client')->except(['logout']);
    }


    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        if(env('RECAPTCHA_MODULE') == 'on')
        {
            $validation['g-recaptcha-response'] = 'required|captcha';
        }else{
            $validation = [];
        }
        $this->validate($request, $validation);
        $request->authenticate();

        $request->session()->regenerate();
            $user = Auth::user();


           if(!\Auth::guard('client')->check()) {
            return redirect('/check');
        }
          
        if($user->type == 'user' &&  !\Auth::guard('client')->check())
        {
            $plan = plan::find($user->plan);
            if($plan)
            {
                if($plan->duration != 'unlimited')
                {
                    $datetime1 = new \DateTime($user->plan_expire_date);
                    $datetime2 = new \DateTime(date('Y-m-d'));
                    $interval = $datetime2->diff($datetime1);
                    $days =$interval->format('%r%a');
                    if($days <= 0)
                    {
                        $user->assignplan(1);
                        return redirect()->intended(RouteServiceProvider::HOME)->with('error',__('Yore plan is expired'));
                    }
                }
            }
        }

        // return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function showClientLoginForm($lang = '')
    {
        if ($lang == '') {
            $lang = env('DEFAULT_LANG') ?? 'en';
        }

        \App::setLocale($lang);

        return view('auth.client_login', compact('lang'));
    }
    
    public function clientLogin(Request $request)
    {
        if(env('RECAPTCHA_MODULE') == 'on')
        {
            $validation['g-recaptcha-response'] = 'required|captcha';
        }else{
            $validation = [];
        }
        $this->validate($request, $validation);
        $this->validate($request, [
            'email'   => 'required|email',
            'password' => 'required|min:4'
        ]);
       

        if (Auth::guard('client')->attempt(['email' => $request->email, 'password' => $request->password], $request->get('remember'))) {
            return redirect()->route('client.home');
        }
        return $this->sendFailedLoginResponse($request);
    }



 public function showLoginForm($lang = '')
    {
        if ($lang == '') {
            $lang = env('DEFAULT_LANG') ?? 'en';
        }

        \App::setLocale($lang);

        return view('auth.login', compact('lang'));
    }



     public function showLinkRequestForm($lang = '')
    {
        if ($lang == '') {
            $lang = env('DEFAULT_LANG') ?? 'en';
        }

        \App::setLocale($lang);

        return view('auth.forgot-password', compact('lang'));
    }



        public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }



protected function sendFailedLoginResponse(Request $request)
{
    throw ValidationException::withMessages([
                                                $this->username() => [trans('auth.failed')],
                                            ]);
}
public function username()
{
    return 'email';
}

}
