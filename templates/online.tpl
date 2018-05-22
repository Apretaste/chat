<h1>Usuarios conectados</h1>

<table width="100%" cellpadding="3">
	{foreach item=user from=$users name=foo}
		{assign var="color" value="black"}
		{if $user->gender eq "M"}{assign var="color" value="#4863A0"}{/if}
		{if $user->gender eq "F"}{assign var="color" value="#F778A1"}{/if}
		<tr>
			<td><small>
				<!--PICTURE-->
				{if $APRETASTE_ENVIRONMENT eq "web"}
					{img src="{$user->picture}" title="@{$user->username}" alt="@{$user->username}" class="profile-small" style="border:2px solid {$color};"}
				{/if}

				<!--USERNAME-->
				{link href="PERFIL @{$user->username}" caption="@{$user->username}" style="color:{$color};"}
				<br/>

				<!--FLAG AND LOCATION-->
				{if $APRETASTE_ENVIRONMENT eq "web" AND $user->country}
					{img src="{$user->country|lower}.png" alt="{$user->country}" class="flag"}
				{/if}
				{$user->location}

				<!--AGE-->
				{if $user->age}
					&middot;
					{$user->age} a&ntilde;os
				{/if}
			</small></td>
			<td align="right">
				{button href="CHAT @{$user->username}" caption="Chatear" size="small" color="grey"}
			</td>
		</tr>
		{if not $smarty.foreach.foo.last}
			<tr><td colspan="2"><hr/></td></tr>
		{/if}
	{/foreach}
</table>

{space10}

<center>
	{button href="CHAT" caption="Mis chats"}
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
		width: 28px;
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
