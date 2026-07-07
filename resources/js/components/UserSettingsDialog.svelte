<script lang="ts">
    // UX-EXPERIMENT: Kunden-Settings als Modal (Alternative zu den
    // /settings-Seiten). Entweder dieses Modal oder die Seiten fliegen
    // nach der Bewertung wieder raus.
    import { Link, page, router } from '@inertiajs/svelte';
    import LogOut from 'lucide-svelte/icons/log-out';
    import MapPin from 'lucide-svelte/icons/map-pin';
    import Package from 'lucide-svelte/icons/package';
    import Settings from 'lucide-svelte/icons/settings';
    import Shield from 'lucide-svelte/icons/shield';
    import UserCog from 'lucide-svelte/icons/user-cog';
    import AddressForm from '@/components/AddressForm.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Separator } from '@/components/ui/separator';
    import { Skeleton } from '@/components/ui/skeleton';
    import { formatPrice } from '@/lib/currency';
    import { fetchJson } from '@/lib/http';
    import { logout } from '@/routes';
    import {
        edit as addressesEdit,
        update as addressesUpdate,
    } from '@/routes/addresses';
    import customerRoutes from '@/routes/customer';
    import { update as profileUpdate } from '@/routes/profile';
    import { edit as securityEdit } from '@/routes/security';
    import type { AddressData, Customer } from '@/types';

    type Pane = 'orders' | 'addresses' | 'profile' | 'security';

    type OrderRow = {
        id: number;
        status: string;
        total_amount: string;
        created_at: string;
    };

    const auth = $derived(page.props.auth);
    const customer = $derived(auth?.user as Customer | undefined);

    let open = $state(false);
    let active = $state<Pane>('orders');

    const panes: { id: Pane; title: string; icon: typeof Package }[] = [
        { id: 'orders', title: 'Bestellungen', icon: Package },
        { id: 'addresses', title: 'Adressen', icon: MapPin },
        { id: 'profile', title: 'Profil', icon: Settings },
        { id: 'security', title: 'Sicherheit', icon: Shield },
    ];

    const statusLabels: Record<string, string> = {
        pending: 'Ausstehend',
        paid: 'Bezahlt',
        processing: 'In Bearbeitung',
        completed: 'Abgeschlossen',
        cancelled: 'Storniert',
        failed: 'Fehlgeschlagen',
        refunded: 'Erstattet',
    };

    const statusClasses: Record<string, string> = {
        completed: 'bg-green-100 text-green-700',
        refunded: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
        failed: 'bg-red-100 text-red-700',
        processing: 'bg-blue-100 text-blue-700',
        paid: 'bg-blue-100 text-blue-700',
        pending: 'bg-yellow-100 text-yellow-700',
    };

    // --- Bestellungen ---
    let orders = $state<OrderRow[] | null>(null);

    // --- Adressen ---
    function emptyAddress(): AddressData {
        return {
            company: '',
            salutation: '',
            first_name: '',
            last_name: '',
            street: '',
            house_number: '',
            address_line2: '',
            zip: '',
            city: '',
            country: 'DE',
            phone: '',
        };
    }

    let addressesLoaded = $state(false);
    let shippingAddress = $state<AddressData>(emptyAddress());
    let billingAddress = $state<AddressData>(emptyAddress());
    let billingSame = $state(true);
    let addressErrors = $state<Record<string, string>>({});
    let savingAddresses = $state(false);

    // --- Profil ---
    let profileName = $state('');
    let profileEmail = $state('');
    let profileErrors = $state<Record<string, string>>({});
    let savingProfile = $state(false);
    let profileInitialized = $state(false);

    $effect(() => {
        if (!open) {
            return;
        }

        if (orders === null) {
            void loadOrders();
        }

        if (!addressesLoaded) {
            void loadAddresses();
        }

        if (!profileInitialized && customer) {
            profileName = customer.name;
            profileEmail = customer.email;
            profileInitialized = true;
        }
    });

    async function loadOrders() {
        const { ok, data } = await fetchJson<{ orders: OrderRow[] }>(
            customerRoutes.orders.url(),
        );

        orders = ok ? data.orders : [];
    }

    async function loadAddresses() {
        const { ok, data } = await fetchJson<{
            shipping: AddressData | null;
            billing: AddressData | null;
        }>(addressesEdit.url());

        if (ok) {
            shippingAddress = { ...emptyAddress(), ...(data.shipping ?? {}) };
            billingAddress = { ...emptyAddress(), ...(data.billing ?? {}) };
            billingSame = data.billing === null;
        }

        addressesLoaded = true;
    }

    function handleAddressUpdate(
        event: CustomEvent<{ prefix: string; key: string; value: string }>,
    ) {
        const { prefix, key, value } = event.detail;

        if (prefix === 'shipping') {
            shippingAddress = { ...shippingAddress, [key]: value };
        } else if (prefix === 'billing') {
            billingAddress = { ...billingAddress, [key]: value };
        }

        const errorKey = `${prefix}.${key}`;

        if (addressErrors[errorKey]) {
            const next = { ...addressErrors };
            delete next[errorKey];
            addressErrors = next;
        }
    }

    function saveAddresses() {
        savingAddresses = true;
        addressErrors = {};

        router.put(
            addressesUpdate.url(),
            {
                shipping: shippingAddress,
                billing_same_as_shipping: billingSame,
                ...(billingSame ? {} : { billing: billingAddress }),
            },
            {
                preserveScroll: true,
                preserveState: true,
                onError: (errors) => {
                    addressErrors = errors as Record<string, string>;
                },
                onFinish: () => {
                    savingAddresses = false;
                },
            },
        );
    }

    function saveProfile() {
        savingProfile = true;
        profileErrors = {};

        router.patch(
            profileUpdate.url(),
            { name: profileName, email: profileEmail },
            {
                preserveScroll: true,
                preserveState: true,
                onError: (errors) => {
                    profileErrors = errors as Record<string, string>;
                },
                onFinish: () => {
                    savingProfile = false;
                },
            },
        );
    }

    function goToSecurityPage() {
        open = false;
        router.visit(securityEdit.url());
    }

    function formatDate(value: string): string {
        return new Date(value).toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    }

    const activeTitle = $derived(
        panes.find((pane) => pane.id === active)?.title ?? '',
    );
</script>

{#if customer}
    <Button
        variant="ghost"
        size="icon"
        onclick={() => (open = true)}
        aria-label="Konto-Einstellungen (Modal)"
    >
        <UserCog class="size-5" />
    </Button>

    <Dialog bind:open>
        <DialogContent
            class="overflow-hidden p-0 md:max-h-[540px] md:max-w-[720px] lg:max-w-[840px]"
        >
            <DialogTitle class="sr-only">Konto-Einstellungen</DialogTitle>
            <DialogDescription class="sr-only">
                Bestellungen, Adressen und Profil verwalten.
            </DialogDescription>

            <div class="flex h-[520px] items-stretch">
                <aside
                    class="hidden w-48 shrink-0 flex-col gap-1 border-r bg-muted/30 p-3 md:flex"
                >
                    {#each panes as pane (pane.id)}
                        <Button
                            variant="ghost"
                            class="w-full justify-start {active === pane.id
                                ? 'bg-muted'
                                : ''}"
                            onclick={() => (active = pane.id)}
                        >
                            <pane.icon class="mr-2 size-4" />
                            {pane.title}
                        </Button>
                    {/each}

                    <Separator class="my-2" />

                    <Button
                        variant="ghost"
                        class="w-full justify-start text-muted-foreground hover:text-destructive"
                        asChild
                    >
                        {#snippet children(props)}
                            <Link
                                href={logout()}
                                as="button"
                                class={props.class}
                                onclick={() => router.flushAll()}
                            >
                                <LogOut class="mr-2 size-4" />
                                Abmelden
                            </Link>
                        {/snippet}
                    </Button>
                </aside>

                <main class="flex min-w-0 flex-1 flex-col">
                    <header
                        class="flex h-14 shrink-0 items-center gap-3 border-b px-5"
                    >
                        <h2 class="text-base font-semibold">{activeTitle}</h2>

                        <!-- Mobile: Pane-Wechsel ohne Sidebar -->
                        <div class="ml-auto flex gap-1 md:hidden">
                            {#each panes as pane (pane.id)}
                                <Button
                                    variant={active === pane.id
                                        ? 'secondary'
                                        : 'ghost'}
                                    size="icon"
                                    onclick={() => (active = pane.id)}
                                    aria-label={pane.title}
                                >
                                    <pane.icon class="size-4" />
                                </Button>
                            {/each}
                        </div>
                    </header>

                    <div class="flex-1 overflow-y-auto p-5">
                        {#if active === 'orders'}
                            {#if orders === null}
                                <div class="flex flex-col gap-3">
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-2/3" />
                                </div>
                            {:else if orders.length === 0}
                                <p
                                    class="py-8 text-center text-sm text-muted-foreground"
                                >
                                    Noch keine Bestellungen vorhanden.
                                </p>
                            {:else}
                                <div class="flex flex-col divide-y">
                                    {#each orders as order (order.id)}
                                        <button
                                            class="flex items-center justify-between gap-3 py-3 text-left hover:bg-muted/40"
                                            onclick={() => {
                                                open = false;
                                                router.visit(
                                                    `/customer/orders/${order.id}`,
                                                );
                                            }}
                                        >
                                            <span
                                                class="font-medium text-[#0d1f44]"
                                            >
                                                #{order.id}
                                            </span>
                                            <span
                                                class="text-sm text-muted-foreground"
                                            >
                                                {formatDate(order.created_at)}
                                            </span>
                                            <span
                                                class={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${statusClasses[order.status] ?? 'bg-yellow-100 text-yellow-700'}`}
                                            >
                                                {statusLabels[order.status] ??
                                                    order.status}
                                            </span>
                                            <span class="text-sm font-semibold">
                                                {formatPrice(
                                                    order.total_amount,
                                                )}*
                                            </span>
                                        </button>
                                    {/each}
                                </div>
                            {/if}
                        {:else if active === 'addresses'}
                            {#if !addressesLoaded}
                                <div class="flex flex-col gap-3">
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-2/3" />
                                </div>
                            {:else}
                                <div class="flex flex-col gap-6">
                                    <div onaddressupdate={handleAddressUpdate}>
                                        <AddressForm
                                            data={shippingAddress}
                                            errors={addressErrors}
                                            prefix="shipping"
                                            legend="Lieferadresse"
                                        />
                                    </div>

                                    <label
                                        class="flex cursor-pointer items-center gap-3"
                                    >
                                        <Checkbox bind:checked={billingSame} />
                                        <span class="text-sm">
                                            Rechnungsadresse ist identisch mit
                                            Lieferadresse
                                        </span>
                                    </label>

                                    {#if !billingSame}
                                        <div
                                            onaddressupdate={handleAddressUpdate}
                                        >
                                            <AddressForm
                                                data={billingAddress}
                                                errors={addressErrors}
                                                prefix="billing"
                                                legend="Rechnungsadresse"
                                            />
                                        </div>
                                    {/if}

                                    <div>
                                        <Button
                                            onclick={saveAddresses}
                                            disabled={savingAddresses}
                                        >
                                            {savingAddresses
                                                ? 'Speichern…'
                                                : 'Adressen speichern'}
                                        </Button>
                                    </div>
                                </div>
                            {/if}
                        {:else if active === 'profile'}
                            <div class="flex max-w-md flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <Label for="modal_profile_name">Name</Label>
                                    <Input
                                        id="modal_profile_name"
                                        bind:value={profileName}
                                    />
                                    {#if profileErrors.name}
                                        <p class="text-xs text-red-500">
                                            {profileErrors.name}
                                        </p>
                                    {/if}
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <Label for="modal_profile_email"
                                        >E-Mail-Adresse</Label
                                    >
                                    <Input
                                        id="modal_profile_email"
                                        type="email"
                                        bind:value={profileEmail}
                                    />
                                    {#if profileErrors.email}
                                        <p class="text-xs text-red-500">
                                            {profileErrors.email}
                                        </p>
                                    {/if}
                                    <p class="text-xs text-muted-foreground">
                                        Nach einer Änderung muss die neue
                                        Adresse erneut bestätigt werden, bevor
                                        bestellt werden kann.
                                    </p>
                                </div>

                                <div>
                                    <Button
                                        onclick={saveProfile}
                                        disabled={savingProfile}
                                    >
                                        {savingProfile
                                            ? 'Speichern…'
                                            : 'Speichern'}
                                    </Button>
                                </div>
                            </div>
                        {:else if active === 'security'}
                            <div class="flex max-w-md flex-col gap-4">
                                <p class="text-sm text-muted-foreground">
                                    Passwort, Zwei-Faktor-Authentifizierung und
                                    Passkeys werden aus Sicherheitsgründen auf
                                    der vollständigen Einstellungsseite
                                    verwaltet (Passwortbestätigung
                                    erforderlich).
                                </p>
                                <div>
                                    <Button
                                        variant="outline"
                                        onclick={goToSecurityPage}
                                    >
                                        <Shield class="mr-2 size-4" />
                                        Sicherheitseinstellungen öffnen
                                    </Button>
                                </div>
                            </div>
                        {/if}
                    </div>
                </main>
            </div>
        </DialogContent>
    </Dialog>
{/if}
