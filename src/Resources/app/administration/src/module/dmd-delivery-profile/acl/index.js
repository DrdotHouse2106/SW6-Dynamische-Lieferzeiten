/**
 * ACL-Rechte für die Lieferprofile (Einstellungen → Rollen → Berechtigungen).
 * Ohne diese Zuordnung könnten nur Admins mit Vollzugriff das Modul nutzen.
 */
Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'settings',
    key: 'dmd_delivery_profile',
    roles: {
        viewer: {
            privileges: ['dmd_delivery_profile:read'],
            dependencies: [],
        },
        editor: {
            privileges: ['dmd_delivery_profile:update'],
            dependencies: ['dmd_delivery_profile.viewer'],
        },
        creator: {
            privileges: ['dmd_delivery_profile:create'],
            dependencies: ['dmd_delivery_profile.viewer', 'dmd_delivery_profile.editor'],
        },
        deleter: {
            privileges: ['dmd_delivery_profile:delete'],
            dependencies: ['dmd_delivery_profile.viewer'],
        },
    },
});
