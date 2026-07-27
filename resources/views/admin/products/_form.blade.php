<select name="product_category_id" class="w-full rounded-lg border-slate-300">
<option value="">No category</option>
@foreach($categories as $category)
<option value="{{ $category->id }}" @selected(old('product_category_id', $product?->product_category_id) == $category->id)>{{ $category->name }}</option>
@endforeach
</select>
<input name="name" value="{{ old('name', $product?->name) }}" required placeholder="Name" class="w-full rounded-lg border-slate-300">
<input name="sku" value="{{ old('sku', $product?->sku) }}" placeholder="SKU" class="w-full rounded-lg border-slate-300">
<input name="product_code" value="{{ old('product_code', $product?->product_code) }}" placeholder="Product code" class="w-full rounded-lg border-slate-300">
<input name="model" value="{{ old('model', $product?->model) }}" placeholder="Model" class="w-full rounded-lg border-slate-300">
<input name="brand" value="{{ old('brand', $product?->brand ?? 'K-Elec') }}" placeholder="Brand" class="w-full rounded-lg border-slate-300">
<input type="number" name="default_warranty_months" value="{{ old('default_warranty_months', $product?->default_warranty_months) }}" placeholder="Warranty months" class="w-full rounded-lg border-slate-300">
<input type="number" name="registration_grace_days" value="{{ old('registration_grace_days', $product?->registration_grace_days ?? 30) }}" placeholder="Grace days" class="w-full rounded-lg border-slate-300">
<textarea name="warranty_terms" class="w-full rounded-lg border-slate-300" rows="3" placeholder="Warranty terms">{{ old('warranty_terms', $product?->warranty_terms) }}</textarea>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true))> Active</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="serial_tracking_enabled" value="1" @checked(old('serial_tracking_enabled', $product?->serial_tracking_enabled ?? true))> Serial tracking</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="manual_verification_allowed" value="1" @checked(old('manual_verification_allowed', $product?->manual_verification_allowed ?? true))> Manual verification allowed</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="receipt_required" value="1" @checked(old('receipt_required', $product?->receipt_required ?? false))> Receipt required</label>
