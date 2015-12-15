<p>
    You have received this email because you asked us to inform you
    when <strong>{{product.label}}</strong> was back in stock.
</p>
<p class="text-center">
    <a href="{{product.url}}" class="btn">
        Click here to view this item at <?=APP_NAME?>
    </a>
</p>
<hr />
{{#product.img}}
    <p class="text-center">
        <img src="{{product.img}}" />
    </p>
{{/product.img}}
<p class="text-center">
    <strong>{{product.label}}</strong>
</p>
{{product.description}}
