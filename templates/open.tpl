<h1>Chats pendientes</h1>

<table width="100%" cellpadding="3">
	{foreach item=item from=$notes name=foo}
		{assign var="color" value="black"}
		{if $item->profile->gender eq "M"}{assign var="color" value="#4863A0"}{/if}
		{if $item->profile->gender eq "F"}{assign var="color" value="#F778A1"}{/if}

		<tr>
			<td><small>
				<!--PICTURE-->
				{if $APRETASTE_ENVIRONMENT eq "web"}
					{img src="{$item->profile->picture_internal}" title="@{$item->profile->username}" alt="@{$item->profile->username}" class="profile-small" style="border:2px solid {$color};"}
				{/if}

				<!--USERNAME-->
				{link href="PERFIL @{$item->profile->username}" caption="@{$item->profile->username}" style="color:{$color};"}
				<br/>

				<!--LAST CHAT SEND-->
				{$item->last_sent|date_format|capitalize}<br/>
			</small></td>
			<td align="right">
				{button href="CHAT @{$item->profile->username}" caption="Escribir" size="small" desc="a:Escriba el texto a enviar*" popup="true" wait="false"}
				{button href="CHAT @{$item->profile->username}" caption="Charla" size="small" color="grey"}
				{button href="CHAT BORRAR @{$item->profile->username}" caption="Eliminar" size="small" color="red"}
				<!--{*button href="CHAT BORRAR @{$item->profile->username}" desc="c:¿Esta seguro de eliminar el chat con @{$item->profile->username}?\nPresione para confirmar*" popup="true" wait="false" caption="Eliminar" size="small" color="red"*}-->
			</td>
		</tr>
		{if not $smarty.foreach.foo.last}
			<tr><td colspan="2"><hr/></td></tr>
		{/if}
	{/foreach}
</table>
{space15}
<center>
	{button href="CHAT" desc="Escriba el @username de su amigo*|a:Escriba el texto a enviar*" caption="+ Nueva Nota" popup="true" wait="false"}
	{button href="CHAT ONLINE" caption="Conectados" color="grey"}
</center>
<style type="text/css">
	hr{
		border: 0;
		height: 0;
		border-top: 1px solid rgba(0, 0, 0, 0.1);
		border-bottom: 1px solid rgba(255, 255, 255, 0.3);
	}

	{if $APRETASTE_ENVIRONMENT eq "web"}
	.profile-small{
		float:left;
		width:28px;
		height:28px;
		border-radius:100px;
		margin-right:10px;
	}
	.flag{
		vertical-align:middle;
		width:20px;
		margin-right:3px;
	}
	{/if}
</style>
