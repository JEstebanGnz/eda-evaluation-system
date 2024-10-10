<!DOCTYPE html>
<html>
<head>
    <title>Reporte por funcionario EDA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"></head>
<body class="mx-5 my-5">

<img src="/images/whiteLogo.png"
     style="max-height: 300px; max-width:300px; object-fit: contain" alt="Logo_universidad">

<p  style="margin-top: 30px"> Sistema de Información para evaluación de funcionarios <strong> EDA </strong></p>

<p> Periodo de evaluación: <strong> {{$assessmentPeriodName}} </strong> </p>

<p> <strong> Reporte para resultados de evaluación</strong> </p>

<p style="margin-bottom: 30px"> Visualizando al funcionario: <strong> {{ucwords($functionaryName)}} </strong></p>

<table class="table" style="max-width: 85%; margin: auto" >
    <thead>
    <tr>
        <th scope="col">Rol</th>
        @foreach($labels as $label)
            <th scope="col">{{$label->name}}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($grades as $grade)
        <tr>
            <td style="text-transform: capitalize">{{$grade->role}}</td>
            <td style="text-align: center">{{$grade->c1}}</td>
            <td style="text-align: center">{{$grade->c2}}</td>
            <td style="text-align: center" >{{$grade->c3}}</td>
            <td style="text-align: center">{{$grade->c4}}</td>
            <td style="text-align: center" >{{$grade->c5}}</td>
            <td style="text-align: center">{{$grade->c6}}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div style="text-align: center; margin-top: 50px">
    <p> <strong> Graficación de Resultados </strong> </p>
    <img src="{{$graph}}" alt="Gráfica de resultados">
</div>


{{--Aquí van las respuestas a las preguntas abiertas de los formularios--}}

@if(count($openAnswers) > 0)
    <div style="margin-top: 30px">
        <p style="font-weight: bold"> Comentarios de la evaluación </p>
        @foreach($openAnswers as $question)
            <p class="black--text pt-2"> Pregunta: </p>
            <p style="font-weight: bold; margin-left: 10px">{{$question->name}}</p>
            <div style="margin-left: 20px">
                @foreach($question->answers as $person)
                    <p style="font-weight: bold"> {{$person->name}} - ({{$person->role}})</p>
                        <p>{{$person->answer}}</p>
                @endforeach
            </div>
        @endforeach
    </div>
@endif


<h6 style="margin-top: 100px; font-weight: bold" > Reporte generado en: {{\Carbon\Carbon::now()}}</h6>
</body>


<script>
    window.addEventListener('load', function (){
        window.print();
    })



</script>

</html>
