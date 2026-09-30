<div class="cart-row">
    <label>Product
        <select name="items[{{ $index }}][product_id]" class="product-choice" required>
            <option value="">Choose product</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} · {{ $product->stock_on_hand }} available · BDT {{ $product->unit_price }}</option>
            @endforeach
            @if(!empty($item['product_id']) && !$products->contains('id', $item['product_id']))
                <option value="{{ $item['product_id'] }}" selected>Unavailable product — choose a replacement</option>
            @endif
        </select>
    </label>
    <label>Quantity
        <input type="number" name="items[{{ $index }}][quantity]" min="1" max="1000000" value="{{ $item['quantity'] ?? 1 }}" required>
    </label>
    <input type="hidden" name="items[{{ $index }}][expected_unit_price]" class="expected-price" value="{{ $item['expected_unit_price'] ?? '' }}">
    <button type="button" class="remove-cart-row secondary">Remove product</button>
</div>
