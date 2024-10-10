<!DOCTYPE html>
<html>
<head>
    <title>Reporte por funcionario: EDA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"></head>
<body class="mx-5 my-5">

<img src="/images/whiteLogo.png"
     style="max-height: 300px; max-width:300px; object-fit: contain" alt="Logo_universidad">

<p  style="margin-top: 30px"> Sistema de Información para evaluación de funcionarios <strong> EDA </strong></p>

<p> Periodo de evaluación: <strong> {{$assessmentPeriodName}} </strong> </p>

<p style="margin-bottom: 30px"> Estimado funcionario: <strong> {{ucwords($functionaryName)}} </strong></p>

@if(count($commitments) > 0)

<p> Mediante este documento se le informa que usted aún posee uno o más compromisos pendientes por diligenciar, como se evidencia a continuación:</p>

<!--Aquí va la tabla-->
<table class="table" style="max-width: 85%; margin: auto" >
    <thead>
    <tr>
        @foreach($commitments[0] as $key => $label)
            <th scope="col">{{$key}}</th>
        @endforeach
    </tr>
    </thead>

    <tbody>
    @foreach($commitments as $commitment)
        <tr>
            @foreach ($commitment as $key => $value)
            <td>{{$value}}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
<p style="margin-top: 20px"> Se le recomienda el cumplimiento oportuno de acuerdo a las fechas máximas de diligenciamiento.</p>


@else
<p> Mediante este documento se le confirma que en el momento usted no posee compromisos pendientes por diligenciar. Por lo cual usted se encuentra a <strong>  Paz y Salvo. </strong></p>
@endif

<h6 style="margin-top: 100px; font-weight: bold" > Reporte generado en: {{\Carbon\Carbon::now()}}</h6>
</body>


<script>
    window.addEventListener('load', function (){
        window.print();
    })
</script>

</html>
