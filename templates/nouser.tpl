{if $contacts}
	<h1>Charlas pendientes</h1>
	<table width="100%">
		{foreach item=item from=$contacts}
		    <tr>
		    	<td>{link href="PERFIL @{$item->username}" caption="@{$item->username}"}</td>
				<td><small>10/01/15 11:34pm</small></td>
		    	<td align="right">
		    		{button href="NOTA @{$item->username} Cambie este texto por su nota" body="" caption="Enviar nota" size="small"}
		    		{button href="NOTA @{$item->username}" caption="Conversaci&oacute;n" size="small" color="grey"}
		    	</td>
		    </tr>
		{/foreach}
	</table>
{else}
	<p>Parece que no ha esta es su primera nota. Para enviar una nota, escriba en el asunto la palabra NOTA seguida del @username de su amigo y luego el mensaje a enviar.</p>
	<p>Por ejemplo: <b>NOTA @amigo Hola compadre como anda todo</b></p>
	{space5}
	<center>
		{button href="NOTA @amigo Hola compadre como anda todo, esta es mi primera nota" caption="Enviar nota"}
	</center>
{/if}