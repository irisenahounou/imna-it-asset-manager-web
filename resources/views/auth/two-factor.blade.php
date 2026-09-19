@extends('layouts.app')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning text-dark text-center fw-bold">Validation 2FA (Code OTP)</div>
            <div class="card-body">
                <p class="text-muted small text-center">Un code temporaire a été généré.</p>
                
                <!-- Astuce Dev : Affiche le code envoyé pour tester facilement -->
                @if(session()->has('2fa:otp_code'))
                    <div class="alert alert-info text-center py-2 mb-3">
                        <small><strong>[Mode Dev] Code OTP généré :</strong></small>
                        <div class="fs-4 fw-bold text-primary">{{ session('2fa:otp_code') }}</div>
                    </div>
                @endif

                <form action="{{ route('2fa.verify') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="code_2fa" class="form-label fw-bold">Saisir le code OTP</label>
                        <input type="text" name="code_2fa" class="form-control text-center fs-3 letter-spacing" maxlength="6" required placeholder="000000" autofocus>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Valider l'authentification</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection