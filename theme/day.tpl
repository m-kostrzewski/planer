<head>
    <link rel="stylesheet" type='text/css' href='{$css}/planer/default.css' />
    <script  scr='{$css}/planer/design.js' ></script>
</head>

<div style='width:80%;margin-right:10%;margin-left:10%;position:relative;'>

<h1 style='text-align:left;margin:5px;'> {$day} </h1>



 <table class="Agrohandel__sale__week info" style="margin-top:15px;margin-bottom:15px;display:none;"> 
    <thead>
        <td class='header_future'> Number </td>
         <td class='header_future'> Zakład </td>
        <td class='header_future'> Ilość sztuk zaplanowanych </td>
        <td class='header_future'> Ilość sztuk rozładowanych  </td>
        <td class='header_future'> Róznica  </td>
        <td class='header_future'> Sztuki padłe  </td>
        <td class='header_future'> Kilometry planowane </td>
        <td class='header_future'> Kilometry przejechane </td>
        <td class='header_future'> Róznica </td>
    </thead>
     {foreach from=$transports item=transport}
    <tr>
        <td class='inter_future'> {$transport.number} <br>
        {$transport.link}
        
         </td>
         <td>{$transport.company}</td>
        <td class='inter_future'> {$transport.bought} </td>
        <td class='inter_future'> {$transport.iloscrozl}  </td>
        <td class='inter_future amount'> {$transport.bought}/{$transport.iloscrozl}  </td>
        <td class='inter_future'> {$transport.iloscpadle}  </td>
        <td class='inter_future'> {$transport.kmplan} </td>
        <td class='inter_future'> {$transport.kmprzej} </td>
        <td class='inter_future km' >  {$transport.kmplan}/{$transport.kmprzej}  </td>
    </tr>
  {/foreach}
    </table>
</div>