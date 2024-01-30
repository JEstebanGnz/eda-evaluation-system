<!DOCTYPE html>
<html>
<head>
    <title>Evaluación de Desempeño Administrativos EDA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"></head>
<body style="font-size: 15px; background-color: #edf2f7">


<section style="width:75%; padding: 5% 10%">
    <h2 style="text-align: center"> Creación de compromiso - Evaluación de Desempeño </h2>
    <div style="background: #ffffff; padding: 5% 5%">
        <p> Apreciado(a) <strong>{{$data['user_name']}}</strong></p>

        <p> Mediante este correo se le informa atentamente que se acaba de registar un nuevo compromiso a su nombre desde la plataforma de Evaluación de Desempeño EDA:</p>

        <p> Información del compromiso:  <strong>{{$data['training_name']}}</strong></p>

        <p> Recuerde que el plazo máximo para dar cumplimiento a este compromiso es el <strong> {{$data['due_date']}} </strong></p>

        <p>Para observar a detalle esta información, ingrese a <span style="text-underline: #00acc1">https://eda.unibague.edu.co/</span> con su cuenta de la Universidad </p>

        <div style="margin-top: 30px">
            <p> Saludos cordiales,
                <br> Gestión Humana - Universidad de Ibagué.
                <br> Tel.: (57) + 8 2760010 ext.: 3004</p>

            <img src="{{$message->embed(public_path().'/images/bigLogo.png')}}" style="max-width:80%; object-fit: contain; margin-top: 0px">
        </div>
    </div>
</section>

</body>

</html>
