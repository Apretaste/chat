{if $notes}
	<h1>Charlas pendientes</h1>
	<table width="100%" cellpadding="3">
		{foreach item=item from=$notes}
			<tr>
				<td>{link href="PERFIL @{$item->username}" caption="@{$item->username}"}</td>
				<td><small>{$item->sent|date_format}</small></td>
				<td align="right">
					{button href="NOTA @{$item->username} Reemplace este texto por su nota" body="" caption="Enviar nota" size="small"}
					{button href="NOTA @{$item->username}" caption="Conversaci&oacute;n" size="small" color="grey"}
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>Parece que esta es su primera nota. Para enviar una nota, escriba en el asunto la palabra NOTA seguida del @username de su amigo y luego el mensaje a enviar.</p>
	<p>Por ejemplo: <b>NOTA @amigo Hola compadre como anda todo</b></p>
	{space5}
	<center>
		{button href="NOTA @amigo Hola como anda todo, esta es mi primera nota" body="Escriba en el asunto el @username de su amigo, personalice la nota y envie este email" caption="Enviar nota"}
	</center>
{/if}
