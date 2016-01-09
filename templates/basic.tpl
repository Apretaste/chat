<h1>Conversaci&oacute;n entre {$username} y t&uacute;</h1>

{foreach item = item from=$notes}
    <p><span style="color: #AAAAAA;"><small>{$item->send_date}</small></span> <b>{$item->from_username}</b>:  {$item->text}</p>
{/foreach}

<center>{button href="NOTA {$username} He leido tu nota'" caption="Responder" color="green" size="small"}
{button href="PERFIL {$username} He leido tu nota'" caption="Perfil de <b>{$username}</b>" color="blue" size="small"}
</center>