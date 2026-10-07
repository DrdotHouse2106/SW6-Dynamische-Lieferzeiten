import template from './dmd-delivery-profile-list.html.twig';

const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.register('dmd-delivery-profile-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            profiles: null,
            isLoading: false,
            total: 0,
            sortBy: 'name',
            sortDirection: 'ASC',
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        profileRepository() {
            return this.repositoryFactory.create('dmd_delivery_profile');
        },

        columns() {
            return [
                {
                    property: 'name',
                    label: this.$tc('dmd-delivery-profile.list.columnName'),
                    routerLink: 'dmd.delivery.profile.detail',
                    primary: true,
                    allowResize: true,
                },
                {
                    property: 'technicalName',
                    label: this.$tc('dmd-delivery-profile.list.columnTechnicalName'),
                    allowResize: true,
                },
                {
                    property: 'rule',
                    label: this.$tc('dmd-delivery-profile.list.columnRule'),
                    sortable: false,
                    allowResize: true,
                },
                {
                    property: 'active',
                    label: this.$tc('dmd-delivery-profile.list.columnActive'),
                    align: 'center',
                    allowResize: true,
                },
            ];
        },
    },

    methods: {
        getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            return this.profileRepository.search(criteria).then((result) => {
                this.profiles = result;
                this.total = result.total;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        ruleSummary(item) {
            const weekday = (day) => this.$tc(`dmd-delivery-profile.weekday.${day}`);
            const occurrence = this.$tc(`dmd-delivery-profile.occurrence.${item.arrivalOccurrence}`);

            return this.$t('dmd-delivery-profile.list.ruleSummary', {
                occurrence,
                arrivalWeekday: weekday(item.arrivalWeekday),
                offset: item.deadlineOffsetDays,
                time: item.deadlineTime,
                shipWeekday: weekday(item.shipWeekday),
            });
        },
    },
});
