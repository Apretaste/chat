{if $notes}
	<h1>Charlas pendientes</h1>
	<table width="100%" cellpadding="3">
		{foreach item=item from=$notes}
			<tr>
				<td>{link href="PERFIL @{$item->username}" caption="@{$item->username}"}</td>
				<td><small>{$item->sent|date_format}</small></td>
				<td align="right">
					{button href="NOTA @{$item->username}" caption="Enviar nota" size="small" body="Escriba en el asunto el texto a enviar a continuacion del @username" desc="Escriba el texto a enviar" popup="true" wait="false"}
					{button href="NOTA @{$item->username}" caption="Conversaci&oacute;n" size="small" color="grey"}
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>Parece que esta es su primera nota. Usa el boton de abajo para comunicarte por primera vez. Por cierto, debes conocer el @username de la persona a quien quieres escribir. Puedes preguntarle, o intentar buscarlo en la {link href="PIZARRA" caption="Pizarra"}.</p>
	{space5}
	<center>
		{button href="NOTA" body="Escriba en el asunto el @username de su amigo seguido del texto a enviar. Por ejemplo: NOTA @alfredo Hola como anda todo?" desc="Escriba el @username de su amigo seguido del texto a enviar. Por ejemplo: @alfredo Hola como anda todo?" caption="Primera Nota" popup="true" wait="false"}
	</center>
{/if}
