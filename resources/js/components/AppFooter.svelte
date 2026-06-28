<script lang="ts">
    import Mail from 'lucide-svelte/icons/mail';
    import Phone from 'lucide-svelte/icons/phone';
</script>

<footer class="mt-auto border-t bg-white">
    <div class="mx-auto max-w-7xl px-4 py-10 lg:px-8">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-3">
            <!-- Company info -->
            <div>
                <p class="text-sm font-bold text-[#1a3a5c]">
                    dormed24.de, ein Angebot der Dormed medizinische Systeme GmbH
                </p>
                <p class="mt-3 text-sm text-muted-foreground">Unterstützung und Beratung unter:</p>
                <p class="mt-2 flex items-center gap-2 text-sm font-bold text-[#1a3a5c]">
                    <Phone class="size-4 text-gray-400" />
                    <a href="tel:+492301188600" class="hover:text-[#1a6bbf]">02301 – 188600</a>
                </p>
                <p class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                    <Mail class="size-4 shrink-0" />
                    <a href="mailto:mail@dormed.de" class="text-[#1a6bbf] hover:underline">mail@dormed.de</a>
                </p>
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
                </ul>
            </div>
        </div>
    </div>
</footer>
