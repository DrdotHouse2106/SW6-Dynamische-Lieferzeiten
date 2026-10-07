import template from './dmd-delivery-profile-detail.html.twig';

const { Mixin } = Shopware;

Shopware.Component.register('dmd-delivery-profile-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            profile: null,
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.profile?.name ?? null),
        };
    },

    computed: {
        profileRepository() {
            return this.repositoryFactory.create('dmd_delivery_profile');
        },

        profileId() {
            return this.$route.params.id ?? null;
        },

        weekdayOptions() {
            return [1, 2, 3, 4, 5, 6, 7].map((value) => ({
                value,
                label: this.$tc(`dmd-delivery-profile.weekday.${value}`),
            }));
        },

        occurrenceOptions() {
            return [1, 2, 3, 4, -2, -1].map((value) => ({
                value,
                label: this.$tc(`dmd-delivery-profile.occurrence.${value}`),
            }));
        },

        isValidDeadlineTime() {
            return /^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/.test(this.profile?.deadlineTime ?? '');
        },

        isValidTechnicalName() {
            return /^[a-z0-9_-]+$/.test(this.profile?.technicalName ?? '');
        },

        isValidExcludedMonths() {
            const value = (this.profile?.excludedMonths ?? '').trim();
            if (value === '') {
                return true;
            }

            const months = value.split(/[\s,;]+/).filter(Boolean);
            const valid = months.every((month) => /^\d+$/.test(month) && Number(month) >= 1 && Number(month) <= 12);

            return valid && new Set(months.map(Number)).size < 12;
        },
    },

    watch: {
        profileId() {
            this.loadEntity();
        },
    },

    created() {
        this.loadEntity();
    },

    methods: {
        loadEntity() {
            if (!this.profileId) {
                this.profile = this.profileRepository.create();
                this.profile.active = true;
                this.profile.arrivalWeekday = 3;
                this.profile.arrivalOccurrence = -1;
                this.profile.deadlineOffsetDays = 7;
                this.profile.deadlineTime = '23:59:59';
                this.profile.shipWeekday = 1;

                return Promise.resolve();
            }

            this.isLoading = true;

            return this.profileRepository.get(this.profileId).then((profile) => {
                this.profile = profile;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        onSave() {
            if (!this.isValidDeadlineTime || !this.isValidTechnicalName || !this.isValidExcludedMonths) {
                this.createNotificationError({
                    message: this.$tc('dmd-delivery-profile.detail.errorValidation'),
                });

                return Promise.resolve();
            }

            if (this.profile.deadlineTime.length === 5) {
                this.profile.deadlineTime = `${this.profile.deadlineTime}:00`;
            }

            this.isLoading = true;
            this.isSaveSuccessful = false;

            return this.profileRepository.save(this.profile).then(() => {
                this.isSaveSuccessful = true;

                if (!this.profileId) {
                    this.$router.push({ name: 'dmd.delivery.profile.detail', params: { id: this.profile.id } });

                    return Promise.resolve();
                }

                return this.loadEntity();
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('dmd-delivery-profile.detail.errorSave'),
                });
            }).finally(() => {
                this.isLoading = false;
            });
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },
    },
});
