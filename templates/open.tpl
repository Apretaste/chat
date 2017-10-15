<h1>Chats pendientes</h1>

<table width="100%" cellpadding="3">
	{foreach item=item from=$notes}
		<tr>
			<td>{link href="PERFIL @{$item->username}" caption="@{$item->username}"}</td>
			<td><small>{$item->sent|date_format}</small></td>
			<td align="right">
				{button href="CHAT @{$item->username}" caption="Enviar nota" size="small" desc="Escriba el texto a enviar" popup="true" wait="false"}
				{button href="CHAT @{$item->username}" caption="Conversaci&oacute;n" size="small" color="grey"}
			</td>
		</tr>
	{/foreach}
</table>

{space15}
<p>Si est&aacute; "disponible" otros usuarios le encontrar&aacute;n para chatear. Si est&aacute; "oculto" su nombre no aparecer&aacute; en la lista de chat.</p>
{space15}

<center>
	{button href="CHAT" desc="Escriba el @username de su amigo|Escriba el texto a enviar" caption="+ Nueva Nota" popup="true" wait="false"}
	{button href="CHAT ONLINE" caption="Conectados" color="blue" wait="false"}
	{if $online == true}
	{button href="CHAT OCULTARSE" caption="Ocultarse" color="red" wait="false" size="medium"}
	{else}
	{button href="CHAT MOSTRARSE" caption="Mostrarse" wait="false" size="medium"}
	{/if}
</center>
