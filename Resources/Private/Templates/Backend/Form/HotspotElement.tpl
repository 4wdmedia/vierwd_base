{$config = json_decode($parameterArray.itemFormElValue, true)}
{if !$config}
	{$config = []}
{/if}
<div class="vierwd-hotspot" data-config="{json_encode($config, JSON_FORCE_OBJECT)}">
	{if count($cropVariants) > 1}
		{* multiple crop areas. Show select for hotspot on an image *}
		<div class="form-control-wrap">
			<select class="vierwd-hotspot__image-variant form-control form-control-adapt">
				{foreach $cropVariants as $cropVariant => $cropConfig}
					{$scaledImageUrl = $scaledImages[$cropVariant]->getPublicUrl(true)}
					{$croppedImageWidth = $croppedImages[$cropVariant]->getProperty('width')}
					{$croppedImageHeight = $croppedImages[$cropVariant]->getProperty('height')}
					<option value="{$cropVariant}" data-image="{$scaledImageUrl}" data-width="{$croppedImageWidth}" data-height="{$croppedImageHeight}">
						{translate key=$cropConfig.title}
					</option>
				{/foreach}
			</select>
		</div>
	{else}
		<input type="hidden" class="vierwd-hotspot__image-variant" value="{key($cropVariants)}">
	{/if}
	<div class="form-control-wrap">
		<select class="vierwd-hotspot__type form-control form-control-adapt">
			<option value="position">Ausgewählte Position</option>
			<option value="multiple">Mehrere Positionen</option>
			<option value="top-left">Oben Links</option>
			<option value="top-right">Oben Rechts</option>
			<option value="bottom-right">Unten Rechts</option>
			<option value="bottom-left">Unten Links</option>
		</select>
	</div>

	<div class="form-control-wrap">
		<input type="text" class="form-control" name="{$parameterArray.itemFormElName}" readonly value="{json_encode($config, JSON_FORCE_OBJECT)}">
	</div>

	<span class="vierwd-hotspot__status"></span><br>

	<div class="vierwd-hotspot__image-container">
		<div class="vierwd-hotspot__position"></div>
		<img src="{$scaledImages[$currentVariant]->getPublicUrl(true)}" data-width="{$croppedImages[$currentVariant]->getProperty('width')}" data-height="{$croppedImages[$currentVariant]->getProperty('height')}" class="vierwd-hotspot__image">
	</div>
</div>