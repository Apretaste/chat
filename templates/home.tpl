<h1>Bienvenido al Chat</h1>

<p>Usa el boton de abajo para escribir tu primera nota. Debes conocer el @username de la persona a quien quieres escribir. Puedes hacer amigos en la {link href="CHAT ONLINE" caption="lista de usuarios conectados"} o en la {link href="PIZARRA" caption="Pizarra"}.</p>

{space15}

<table bgcolor="#F2F2F2" width="100%">
	<tr>
		<td><small>Si est&aacute; "disponible" otros usuarios le ver&aacute;n. Si est&aacute; "oculto", no aparecer&aacute;.</small></td>
		<td align="right">
			{if $online}{button href="CHAT OCULTARSE" caption="Ocultarse" color="grey" wait="false" size="small"}
			{else}{button href="CHAT MOSTRARSE" caption="Mostrarse" wait="false" size="small"}{/if}
		</td>
	</tr>
</table>

{space15}

<center>
	{button href="CHAT" desc="Escriba el @username de su amigo|Escriba el texto a enviar" caption="Primera Nota" popup="true" wait="false"}
	{button href="CHAT ONLINE" caption="Conectados" color="blue" wait="false"}
</center>
