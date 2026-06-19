<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">

            <div class="card">
                <div class="card-body">

                    <h4 class="text-center mb-3">Register</h4>

                    <form method="POST" action="/register">
                        @csrf

                        <input type="text" name="name"
                               class="form-control mb-2"
                               placeholder="Name" required>

                        <input type="email" name="email"
                               class="form-control mb-2"
                               placeholder="Email" required>

                        <input type="password" name="password"
                               class="form-control mb-3"
                               placeholder="Password" required>

                        <button class="btn btn-success w-100">
                            Register
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
