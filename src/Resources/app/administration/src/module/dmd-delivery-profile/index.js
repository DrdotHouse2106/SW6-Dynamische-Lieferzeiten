import './page/dmd-delivery-profile-list';
import './page/dmd-delivery-profile-detail';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('dmd-delivery-profile', {
    type: 'plugin',
    name: 'dmd-delivery-profile',
    title: 'dmd-delivery-profile.general.mainMenuItemGeneral',
    description: 'dmd-delivery-profile.general.descriptionTextModule',
    color: '#9AA8B5',
    icon: 'regular-calendar',
    entity: 'dmd_delivery_profile',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },

    routes: {
        index: {
            component: 'dmd-delivery-profile-list',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index.plugins',
            },
        },
        detail: {
            component: 'dmd-delivery-profile-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'dmd.delivery.profile.index',
            },
        },
        create: {
            component: 'dmd-delivery-profile-detail',
            path: 'create',
            meta: {
                parentPath: 'dmd.delivery.profile.index',
            },
        },
    },

    settingsItem: {
        group: 'plugins',
        to: 'dmd.delivery.profile.index',
        icon: 'regular-calendar',
        label: 'dmd-delivery-profile.general.mainMenuItemGeneral',
    },
});
