<h1>Chats pendientes</h1>

<table width="100%" cellpadding="3">
	{foreach item=item from=$notes}
		<tr>
			<td>{link href="PERFIL @{$item->username}" caption="@{$item->username}"}</td>
			<td><small>{$item->sent|date_format}</small></td>
			<td align="right">
				{button href="CHAT @{$item->username}" caption="Enviar nota" size="small" desc="a:Escriba el texto a enviar*" popup="true" wait="false"}
				{button href="CHAT @{$item->username}" caption="Conversaci&oacute;n" size="small" color="grey"}
			</td>
		</tr>
	{/foreach}
</table>

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
	{button href="CHAT" desc="Escriba el @username de su amigo*|a:Escriba el texto a enviar*" caption="+ Nueva Nota" popup="true" wait="false"}
	{button href="CHAT ONLINE" caption="Conectados" color="blue"}
</center>
