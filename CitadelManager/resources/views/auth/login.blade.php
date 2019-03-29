@extends('layouts.app')

@section('content')

<div class="login-screen-bg">
    <div class="login-form padding20 block-shadow">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <h1 class="text-light">Administrator Login</h1>
                    <br>
                    <br>
                    <div class="panel-body">
                        <form class="form-horizontal" id="login_form" role="form" method="POST" action="{{ route('login') }}">
                            {{ csrf_field() }}

                            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">

                                <div class="col-md-6">
                                    <!-- User email address input. -->
                                    <div class="input-control text full-size" data-role="input">
                                        <label for="email">E-Mail Address:</label>
                                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
                                        <button class="button helper-button clear"><span class="mif-cross"></span></button>

                                        @if ($errors->has('email'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <br>                        
                            <br>

                            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">

                                <div class="col-md-6">
                                    <div class="input-control password full-size" data-role="input">
                                        <label for="user_password">Password:</label>
                                        <input                                         
                                            type="password" 
                                            class="form-control"
                                            name="password" 
                                            id="password">

                                        <button class="button helper-button reveal"><span class="mif-looks"></span></button>

                                        <!-- Icons for success or failire in form validation. -->
                                        <span class="input-state-error mif-warning"></span>
                                        <span class="input-state-success mif-checkmark"></span>
                                    </div>

                                    @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                    @endif

                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    <div>
                                        <label class="input-control checkbox">
                                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                   <span class="check"></span>
                                            <span class="caption">Remember Me</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <a style="display: block; margin-bottom: 10px;" href="{{ route('password.request') }}">Forgot Password?</a>

                            <div class="form-group">
                                <button style="float: left; margin-right: 10px;" type="submit" class="button primary">Login</button>
                                <button onclick="window.location.href = '{{ route('login.wordpress') }}';" style="float: left;" type="button" class="button secondary">
                                    Login With CloudVeil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Script to fade/scale in the login window. -->
<script>
    $(function ()
    {
        var form = $(".login-form");
        form.css({
            opacity: 1,
            "-webkit-transform": "scale(1)",
            "transform": "scale(1)",
            "-webkit-transition": ".5s",
            "transition": ".5s"
        });
    });
</script>
@endsection
