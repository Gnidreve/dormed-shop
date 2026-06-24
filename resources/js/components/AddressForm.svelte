<script lang="ts">
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import type { AddressData } from '@/types/cart';

    let {
        data,
        errors = {},
        prefix,
        legend,
    }: {
        data: AddressData;
        errors?: Record<string, string>;
        prefix: string;
        legend: string;
    } = $props();

    function update(key: keyof AddressData, value: string) {
        // Dispatch to parent via input event
        const event = new CustomEvent('addressupdate', {
            detail: { prefix, key, value },
            bubbles: true,
        });
        document.getElementById(`address-form-${prefix}`)?.dispatchEvent(event);
    }
</script>

<div id="address-form-{prefix}" class="space-y-4">
    <h3 class="font-bold text-gray-900">{legend}</h3>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <Label for="{prefix}.company">Firma (optional)</Label>
            <Input
                id="{prefix}.company"
                value={data.company}
                oninput={e => update('company', e.currentTarget.value)}
                placeholder="Firmenname"
                class={errors[`${prefix}.company`] ? 'border-red-500' : ''}
            />
            {#if errors[`${prefix}.company`]}
                <p class="mt-1 text-xs text-red-500">{errors[`${prefix}.company`]}</p>
            {/if}
        </div>

        <div>
            <Label for="{prefix}.salutation">Anrede</Label>
            <Select
                value={data.salutation}
                onValueChange={(v: string) => update('salutation', v)}
            >
                <SelectTrigger id="{prefix}.salutation" class="w-full">
                    {#if data.salutation}
                        <div data-slot="select-value">{data.salutation}</div>
                    {:else}
                        <div data-slot="select-value" class="text-muted-foreground">—</div>
                    {/if}
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">—</SelectItem>
                    <SelectItem value="Herr">Herr</SelectItem>
                    <SelectItem value="Frau">Frau</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div></div>

        <div>
            <Label for="{prefix}.first_name">
                Vorname <span class="text-red-500">*</span>
            </Label>
            <Input
                id="{prefix}.first_name"
                value={data.first_name}
                oninput={e => update('first_name', e.currentTarget.value)}
                class={errors[`${prefix}.first_name`] ? 'border-red-500' : ''}
            />
            {#if errors[`${prefix}.first_name`]}
                <p class="mt-1 text-xs text-red-500">{errors[`${prefix}.first_name`]}</p>
            {/if}
        </div>

        <div>
            <Label for="{prefix}.last_name">
                Nachname <span class="text-red-500">*</span>
            </Label>
            <Input
                id="{prefix}.last_name"
                value={data.last_name}
                oninput={e => update('last_name', e.currentTarget.value)}
                class={errors[`${prefix}.last_name`] ? 'border-red-500' : ''}
            />
            {#if errors[`${prefix}.last_name`]}
                <p class="mt-1 text-xs text-red-500">{errors[`${prefix}.last_name`]}</p>
            {/if}
        </div>

        <div class="sm:col-span-1">
            <Label for="{prefix}.street">
                Straße <span class="text-red-500">*</span>
            </Label>
            <Input
                id="{prefix}.street"
                value={data.street}
                oninput={e => update('street', e.currentTarget.value)}
                class={errors[`${prefix}.street`] ? 'border-red-500' : ''}
            />
            {#if errors[`${prefix}.street`]}
                <p class="mt-1 text-xs text-red-500">{errors[`${prefix}.street`]}</p>
            {/if}
        </div>

        <div>
            <Label for="{prefix}.house_number">
                Hausnr. <span class="text-red-500">*</span>
            </Label>
            <Input
                id="{prefix}.house_number"
                value={data.house_number}
                oninput={e => update('house_number', e.currentTarget.value)}
                class={errors[`${prefix}.house_number`] ? 'border-red-500' : ''}
            />
            {#if errors[`${prefix}.house_number`]}
                <p class="mt-1 text-xs text-red-500">{errors[`${prefix}.house_number`]}</p>
            {/if}
        </div>

        <div class="sm:col-span-2">
            <Label for="{prefix}.address_line2">Adresszusatz (optional)</Label>
            <Input
                id="{prefix}.address_line2"
                value={data.address_line2}
                oninput={e => update('address_line2', e.currentTarget.value)}
                placeholder="z.B. c/o, Gebäude, Etage"
            />
        </div>

        <div>
            <Label for="{prefix}.zip">
                PLZ <span class="text-red-500">*</span>
            </Label>
            <Input
                id="{prefix}.zip"
                value={data.zip}
                oninput={e => update('zip', e.currentTarget.value)}
                class={errors[`${prefix}.zip`] ? 'border-red-500' : ''}
            />
            {#if errors[`${prefix}.zip`]}
                <p class="mt-1 text-xs text-red-500">{errors[`${prefix}.zip`]}</p>
            {/if}
        </div>

        <div>
            <Label for="{prefix}.city">
                Ort <span class="text-red-500">*</span>
            </Label>
            <Input
                id="{prefix}.city"
                value={data.city}
                oninput={e => update('city', e.currentTarget.value)}
                class={errors[`${prefix}.city`] ? 'border-red-500' : ''}
            />
            {#if errors[`${prefix}.city`]}
                <p class="mt-1 text-xs text-red-500">{errors[`${prefix}.city`]}</p>
            {/if}
        </div>

        <div class="sm:col-span-1">
            <Label for="{prefix}.country">Land</Label>
            <Select
                value={data.country}
                onValueChange={(v: string) => update('country', v)}
            >
                <SelectTrigger id="{prefix}.country" class="w-full">
                    <div data-slot="select-value">
                        {#if data.country === 'DE'}Deutschland{:else if data.country === 'AT'}Österreich{:else if data.country === 'CH'}Schweiz{:else if data.country === 'NL'}Niederlande{:else if data.country === 'BE'}Belgien{:else if data.country === 'LU'}Luxemburg{:else if data.country === 'FR'}Frankreich{:else}Deutschland{/if}
                    </div>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="DE">Deutschland</SelectItem>
                    <SelectItem value="AT">Österreich</SelectItem>
                    <SelectItem value="CH">Schweiz</SelectItem>
                    <SelectItem value="NL">Niederlande</SelectItem>
                    <SelectItem value="BE">Belgien</SelectItem>
                    <SelectItem value="LU">Luxemburg</SelectItem>
                    <SelectItem value="FR">Frankreich</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div>
            <Label for="{prefix}.phone">Telefon (optional)</Label>
            <Input
                id="{prefix}.phone"
                value={data.phone}
                oninput={e => update('phone', e.currentTarget.value)}
                placeholder="+49 176 12345678"
            />
        </div>
    </div>
</div>
