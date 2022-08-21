<div id="t{$element.bytes}">
  <dl>
{foreach from=$element.fields item=field}    {include file='field.tpl'}
{/foreach}  </dl>
</div>
