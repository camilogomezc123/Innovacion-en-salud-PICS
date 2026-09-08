@extends('portal.layout')

@section('title', 'Ingresar')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Bienvenido a POSUCI 360 Conecta</h1>
                <p class="text-muted mb-4">Ingresa con el correo y la contraseña que te dio tu equipo de recuperación.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('portal.login.submit') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-posuci btn-lg w-100">Ingresar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
