<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">

            <div class="card">
                <div class="card-body">

                    <h4 class="text-center mb-3">Login</h4>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="/login">
                        @csrf

                        <input type="email" name="email"
                               class="form-control mb-2"
                               placeholder="Email" required>

                        <input type="password" name="password"
                               class="form-control mb-3"
                               placeholder="Password" required>

                        <button class="btn btn-dark w-100">
                            Login
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="/forgot-password">Forgot Password?</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
