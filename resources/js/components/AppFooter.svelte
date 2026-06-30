<script lang="ts">
    import Mail from 'lucide-svelte/icons/mail';
    import Phone from 'lucide-svelte/icons/phone';
    import Printer from 'lucide-svelte/icons/printer';
</script>

<footer class="mt-auto border-t bg-white">
    <div class="mx-auto max-w-7xl px-4 py-10 lg:px-8">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Company info -->
            <div>
                <img src="/logo.svg" alt="dormed 24" class="mb-4 h-8 w-auto" />
                <p class="text-sm font-bold text-[#1a3a5c]">
                    ein Angebot der Dormed medizinische Systeme GmbH
                </p>
                <p class="mt-3 text-sm text-muted-foreground">Unterstützung und Beratung unter:</p>
                <p class="mt-2 flex items-center gap-2 text-sm font-bold text-[#1a3a5c]">
                    <Phone class="size-4 shrink-0 text-gray-400" />
                    <a href="tel:+492301188600" class="hover:text-[#1a6bbf]">02301 – 188600</a>
                </p>
                <p class="mt-1 flex items-center gap-2 text-sm font-bold text-[#1a3a5c]">
                    <Printer class="size-4 shrink-0 text-gray-400" />
                    <span>02301 / 188-620</span>
                </p>
                <p class="mt-1 flex items-center gap-2 text-sm font-bold text-[#1a3a5c]">
                    <Mail class="size-4 shrink-0 text-gray-400" />
                    <a href="mailto:mail@dormed.de" class="hover:text-[#1a6bbf]">mail@dormed.de</a>
                </p>
            </div>

            <!-- Leistungen -->
            <div>
                <p class="text-sm font-bold text-[#1a3a5c]">Leistungen</p>
                <ul class="mt-3 space-y-2">
                    <li class="text-sm text-muted-foreground">Beratung</li>
                    <li class="text-sm text-muted-foreground">Finanzierung &amp; Leasing</li>
                    <li class="text-sm text-muted-foreground">Lieferung &amp; Installation</li>
                    <li class="text-sm text-muted-foreground">Wartung &amp; Reparatur</li>
                    <li class="text-sm text-muted-foreground">Netzwerkanbindung</li>
                    <li class="text-sm text-muted-foreground">Schulung &amp; Einweisung</li>
                    <li class="text-sm text-muted-foreground">Garantie &amp; Versicherung</li>
                    <li class="text-sm text-muted-foreground">Inzahlungnahme</li>
                </ul>
            </div>

            <!-- Informationen -->
            <div>
                <p class="text-sm font-bold text-[#1a3a5c]">Informationen</p>
                <ul class="mt-3 space-y-2">
                    <li>
                        <a href="/versand" class="text-sm text-[#1a6bbf] hover:underline">
                            Versand
                        </a>
                    </li>
                    <li>
                        <a href="/zahlung" class="text-sm text-[#1a6bbf] hover:underline">
                            Zahlung
                        </a>
                    </li>
                    <li>
                        <a href="/faq" class="text-sm text-[#1a6bbf] hover:underline">FAQ</a>
                    </li>
                </ul>
            </div>

            <!-- Unternehmen -->
            <div>
                <p class="text-sm font-bold text-[#1a3a5c]">Unternehmen</p>
                <ul class="mt-3 space-y-2">
                    <li>
                        <a href="/impressum" class="text-sm text-[#1a6bbf] hover:underline">Impressum</a>
                    </li>
                    <li>
                        <a href="/agb" class="text-sm text-[#1a6bbf] hover:underline">AGB</a>
                    </li>
                    <li>
                        <a href="/datenschutz" class="text-sm text-[#1a6bbf] hover:underline">Datenschutz</a>
                    </li>
                    <li>
                        <a href="/widerrufsbelehrung" class="text-sm text-[#1a6bbf] hover:underline">Widerrufsbelehrung</a>
                    </li>
                    <li>
                        <a href="https://dormed.de/kontakt" target="_blank" rel="noopener noreferrer" class="text-sm text-[#1a6bbf] hover:underline">Kontakt</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
