<h1>Charla con @{$friendUsername}</h1>

{if not $chats}
	<p>Usted no ha chateado con @{$friendUsername} anteriormente. Presione el bot&oacute;n a continuaci&oacute;n para enviarle una primera nota.</p>
{else}
	<table width="100%" cellspacing="0" cellpadding="5" border=0>
	{foreach item=item from=$chats}
		<tr><td {if $friendUsername == $item->username}bgcolor="#F2F2F2"{/if}>
			<span style="color: #AAAAAA;"><small>{$item->sent|date_format:"%e/%m/%Y %I:%M %p"}</small></span><br/>
			<b>{link href="PERFIL @{$item->username}" caption="@{$item->username}"}</b>:
			<span style="color:{if $friendUsername == $item->username}#000000{else}#000066{/if};">{$item->text}</span>
		</td></tr>
	{/foreach}
	</table>
{/if}

{space15}

<center>
	{button href="CHAT @{$friendUsername}" caption="Escribir" size="medium" desc="Escriba el texto a enviar" popup="true" wait="false"}
</center>
