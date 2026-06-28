import { createInertiaApp } from '@inertiajs/svelte';
import AppLayout from '@/layouts/AppLayout.svelte';
import AuthLayout from '@/layouts/AuthLayout.svelte';
import CustomerLayout from '@/layouts/CustomerLayout.svelte';
import SettingsLayout from '@/layouts/settings/Layout.svelte';
import { initializeFlashToast } from '@/lib/flash-toast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('Admin/') && name !== 'Admin/Login':
                return AppLayout;
            case name === 'Admin/Login':
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [CustomerLayout, SettingsLayout];
            default:
                return null;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will listen for flash toast data from the server...
initializeFlashToast();
