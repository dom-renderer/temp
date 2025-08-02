<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title> {{ Helper::title() }} - Unauthorized </title>

    <link href="{{ asset('assets/css/style-dark.min.css') }}" rel="stylesheet">


</head>
<body class="theme-blue">

	<main class="main h-100 w-100">
		<div class="container h-100">
			<div class="row h-100">
				<div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">

						<div class="text-center">
							<h1 class="display-1 fw-bold">403</h1>
							<p class="h1">Unauthorized.</p>
							<p class="h2 fw-normal mt-3 mb-4">You are not authorized to peform this action.</p>
							<a class='btn btn-primary btn-lg' href='{{ url('') }}'>Return to dashboard</a>
						</div>

					</div>
				</div>
			</div>
		</div>
	</main>
</body>
<script>

</script>

</html>