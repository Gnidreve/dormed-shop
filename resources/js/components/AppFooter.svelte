<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Mail from 'lucide-svelte/icons/mail';
    import Phone from 'lucide-svelte/icons/phone';
    import Printer from 'lucide-svelte/icons/printer';

    type ContactInfo = {
        email: string;
        phone: string;
        fax: string;
        phone_href: string;
        fax_href: string;
    };

    const contact = $derived(page.props.contact as ContactInfo);
</script>

<footer class="mt-auto border-t bg-white">
    <div class="mx-auto max-w-7xl px-4 py-10 lg:px-8">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <img src="/logo.svg" alt="dormed 24" class="mb-4 h-8 w-auto" />
                <p class="text-sm font-bold text-[#1a3a5c]">
                    ein Angebot der Dormed medizinische Systeme GmbH
                </p>
                <p class="mt-3 text-sm text-muted-foreground">
                    Unterstuetzung und Beratung unter:
                </p>
                <p
                    class="mt-2 flex items-center gap-2 text-sm font-bold text-[#1a3a5c]"
                >
                    <Phone class="size-4 shrink-0 text-gray-400" />
                    <a href={contact.phone_href} class="hover:text-[#1a6bbf]"
                        >{contact.phone}</a
                    >
                </p>
                <p
                    class="mt-1 flex items-center gap-2 text-sm font-bold text-[#1a3a5c]"
                >
                    <Printer class="size-4 shrink-0 text-gray-400" />
                    <a href={contact.fax_href} class="hover:text-[#1a6bbf]"
                        >{contact.fax}</a
                    >
                </p>
                <p
                    class="mt-1 flex items-center gap-2 text-sm font-bold text-[#1a3a5c]"
                >
                    <Mail class="size-4 shrink-0 text-gray-400" />
                    <a
                        href={`mailto:${contact.email}`}
                        class="hover:text-[#1a6bbf]">{contact.email}</a
                    >
                </p>
            </div>

            <div>
                <p class="text-sm font-bold text-[#1a3a5c]">Leistungen</p>
                <ul class="mt-3 space-y-2">
                    <li>
                        <a
                            href="https://dormed.de/leistungen/beratung"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                        >
                            Beratung
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://dormed.de/leistungen/finanzierung"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                        >
                            Finanzierung &amp; Leasing
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://dormed.de/leistungen/lieferung"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                        >
                            Lieferung &amp; Installation
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://dormed.de/leistungen/wartung-reparatur"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                        >
                            Wartung &amp; Reparatur
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://dormed.de/leistungen/netzwerkanbindung"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                        >
                            Netzwerkanbindung
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://dormed.de/leistungen/schulung-einweisung"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                        >
                            Schulung &amp; Einweisung
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://dormed.de/leistungen/garantie-versicherung"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                        >
                            Garantie &amp; Versicherung
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://dormed.de/leistungen/inzahlungnahme"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                        >
                            Inzahlungnahme
                        </a>
                    </li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-bold text-[#1a3a5c]">Informationen</p>
                <ul class="mt-3 space-y-2">
                    <li>
                        <Link
                            href="/informationen/versand"
                            class="text-sm text-[#1a6bbf] hover:underline"
                            >Versand</Link
                        >
                    </li>
                    <li>
                        <Link
                            href="/informationen/zahlung"
                            class="text-sm text-[#1a6bbf] hover:underline"
                            >Zahlung</Link
                        >
                    </li>
                    <li>
                        <Link
                            href="/informationen/faq"
                            class="text-sm text-[#1a6bbf] hover:underline"
                            >FAQ</Link
                        >
                    </li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-bold text-[#1a3a5c]">Unternehmen</p>
                <ul class="mt-3 space-y-2">
                    <li>
                        <Link
                            href="/unternehmen/impressum"
                            class="text-sm text-[#1a6bbf] hover:underline"
                            >Impressum</Link
                        >
                    </li>
                    <li>
                        <Link
                            href="/unternehmen/agb"
                            class="text-sm text-[#1a6bbf] hover:underline"
                            >AGB</Link
                        >
                    </li>
                    <li>
                        <Link
                            href="/unternehmen/datenschutz"
                            class="text-sm text-[#1a6bbf] hover:underline"
                            >Datenschutz</Link
                        >
                    </li>
                    <li>
                        <Link
                            href="/unternehmen/widerrufsbelehrung"
                            class="text-sm text-[#1a6bbf] hover:underline"
                            >Widerrufsbelehrung</Link
                        >
                    </li>
                    <li>
                        <a
                            href="https://dormed.de/kontakt"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#1a6bbf] hover:underline"
                            >Kontakt</a
                        >
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
