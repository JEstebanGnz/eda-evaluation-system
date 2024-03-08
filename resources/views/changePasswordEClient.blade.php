<!DOCTYPE html>
<html>
<head>
    <title>Evaluación de Desempeño Administrativos EDA - Creación de credenciales de acceso </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"></head>
<body style="font-size: 15px; background-color: #edf2f7">


<section style="width:75%; padding: 5% 10%">

    <h2 style="text-align: center"> EDA - Universidad de Ibagué </h2>

    <div style="background: #ffffff; padding: 5% 5%">

        <p> Apreciado(a) <strong>{{$data['name']}}</strong></p>

        <p> Desde Gestión Humana se lleva a cabo el proceso de evaluación de funcionarios mediante el sistema de información EDA.</p>

        <p> De acuerdo a su solicitud, se ha generado una nueva contraseña para que usted pueda acceder a la plataforma de evaluación.</p>

        <p> Su nueva contraseña es la siguiente: {{$data['password']}}</p>

        <p>Para realizar la evaluación, recuerde que debe ingresar a <span style="text-underline: #00acc1">https://eda.unibague.edu.co/</span></p>

        <div style="margin-top: 30px">
            <p> Saludos cordiales,
                <br> Gestión Humana - Universidad de Ibagué.
                <br> Tel.: (57) + 8 2760010 ext.: 1202</p>

            <img src="{{$message->embed(public_path().'/images/bigLogo.png')}}" style="max-width:80%; object-fit: contain; margin-top: 0px">

        </div>

    </div>


</section>

</body>

</html>
