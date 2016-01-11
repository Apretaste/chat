<h1>Deseas enviar una nota?</h1>
<p align="justify">Para enviar una nota escribe en el asunto la palabra NOTA seguida del nombre de usuario de tu amigo y 
luego el mensaje a enviar. 
{space10}
Por ejemplo: <b>NOTA amigo1 Hola amigo</b></p>
<center>{button href="NOTA amigo1 Hola amigo" caption="Probar NOTA" size="medium"}</center>
{space5}
<h1>Tus contactos</h1>
{if $contacts}
<p>Al no especificar el nombre de usuario en el asunto, te mostramos a continuaci&oacute;n una lista
de los usuarios con los cuales has intercambiado notas.</p>
<table width="99%">
    <tr><th>Usuario</th><th>&Uacute;ltima nota</th><th>Opciones</th></tr>
    <tr><td colspan="3"><hr /></td></tr>
{foreach item=item from=$contacts}
    <tr><td>{$item->username}</td>
    <td><b>{$item->last_note['from']}:</b> {$item->last_note['note']}</td>
    <td align="right">{button href="PERFIL {$item->username}" caption="Perfil" size="small" color="red"}
    {button href="NOTA {$item->username} Hola que tal, como andas.." caption="Enviar nota" size="small" color="green"}
    {button href="NOTA {$item->username}" caption="Conversaci&oacute;n" size="small" color="blue"}</td>
    </tr>
    <tr><td colspan="3"><hr /></td></tr>
{/foreach}
</table>
{else}
No has compartido notas con nadie hasta ahora. 
{/if}