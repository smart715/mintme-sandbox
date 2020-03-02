import '../../js/main';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import {mount} from '../testHelper';
import Trading from '../../js/components/trading/Trading';
import {BPagination, BTable} from 'bootstrap-vue';
import moxios from 'moxios';
import Axios from '../../js/axios';

Vue.component('b-pagination', BPagination);
Vue.component('b-table', BTable);

describe('Trading', () => {
    beforeEach(() => {
        moxios.install();
    });
    afterEach(() => {
        moxios.uninstall();
    });
    const $routing = {generate: () => 'URL'};
    const localVue = createLocalVue();
    localVue.use(Axios);

    const wrapper = shallowMount(Trading, {
        localVue,
        mocks: {
            $routing,
        },
        computed: {
            loaded() {
              return true;
            },
        },
        propsData: {
            enableUsd: true,
        },
    });

    let market = {pair: 'tok1', change: '0', lastPrice: '0', volume: '0'};
    let deployedToken = {pair: 'tok1', change: '0', lastPrice: '0', volume: '0', tokenized: true};
    let marketOnTop = [{pair: 'BTC/MINTME', change: '0', lastPrice: '0', volume: '0'}];

    it('Show USD in dropdown option if enableUSD is true', () => {
        expect(wrapper.find('.usdOption').exists()).to.deep.equal(true);
    });
    it('show message if there are not deployed tokens yet', () => {
        wrapper.vm.marketFilters.selectedFilter = 'deployed';
        expect(wrapper.html().includes('No one deployed his token yet')).to.deep.equal(true);
        wrapper.vm.sanitizedMarkets = market;
        wrapper.vm.sanitizedMarketsOnTop = marketOnTop;
        expect(wrapper.html().includes('No one deployed his token yet')).to.deep.equal(false);
    });
    it('show message if user has no any token yet', () => {
        wrapper.vm.sanitizedMarketsOnTop = marketOnTop;
        wrapper.vm.sanitizedMarkets = {};
        wrapper.vm.marketFilters.selectedFilter = 'user';
        expect(wrapper.html().includes('No any token yet')).to.deep.equal(true);
        wrapper.vm.sanitizedMarkets = market;
        wrapper.vm.sanitizedMarketsOnTop = marketOnTop;
        expect(wrapper.html().includes('No any token yet')).to.deep.equal(false);
    });
    it('show rest of token link', () => {
        wrapper.vm.marketFilters.selectedFilter = 'all';
        expect(wrapper.html().includes('Show rest of tokens')).to.deep.equal(false);
        wrapper.setProps({userId: 1});
        wrapper.vm.marketFilters.selectedFilter = 'user';
        expect(wrapper.html().includes('Show rest of tokens')).to.deep.equal(true);
        wrapper.vm.marketFilters.selectedFilter = 'deployed';
        expect(wrapper.html().includes('Show rest of tokens')).to.deep.equal(true);
    });
    it('show all tokens if there are not deployed tokens', () => {
        wrapper.vm.marketFilters.userSelected = false;
        wrapper.vm.marketFilters.selectedFilter = 'all';
        wrapper.vm.markets = JSON.stringify({
            TOK000000000001WEB: ['tok1', 'WEB'],
            TOK000000000002WEB: ['tok2', 'WEB'],
            TOK000000000003BTC: ['WEB', 'BTC'],
        });
        expect(wrapper.vm.markets).to.not.be.null;
    });
    it('show only tokens deployed if user selected the option', () => {
        wrapper.vm.marketFilters.userSelected = true;
        wrapper.vm.marketFilters.selectedFilter = 'deployed';
        expect(wrapper.vm.sanitizedMarkets).to.not.be.empty;
    });
    it('show only deployed tokens if there are at least one dployed', () => {
       wrapper.vm.sanitizedMarketsOnTop = marketOnTop;
       wrapper.vm.sanitizedMarkets = deployedToken;
        wrapper.vm.marketFilters.selectedFilter = 'deployed';
        expect(wrapper.vm.sanitizedMarkets).to.not.be.empty;
    });
    it('show all tokens when user selected the option', () => {
        wrapper.vm.marketFilters.userSelected = true;
        wrapper.vm.marketFilters.selectedFilter = 'all';
        expect(wrapper.vm.marketFilters.selectedFilter).to.deep.equal('all');
        expect(wrapper.vm.markets).to.not.be.null;
     });
     it('show user tokens owns when user selected the option', () => {
        wrapper.vm.marketFilters.userSelected = true;
        wrapper.vm.marketFilters.selectedFilter = 'user';
        wrapper.vm.sanitizedMarkets = market;
        expect(wrapper.vm.marketFilters.selectedFilter).to.deep.equal('user');
        expect(wrapper.vm.sanitizedMarkets).to.not.be.empty;
     });

    describe('marketCapFormatter() function should return ', () => {
        it('dash(-) if market is for token and monthVolume less than minimumVolumeForMarketcap', () => {
            const wrapper = shallowMount(Trading, {
                mocks: {
                    $routing,
                },
                propsData: {
                    minimumVolumeForMarketcap: 10,
                },
            });
            let item = {
                base: 'MINTME',
                monthVolume: 9,

            };
            expect(wrapper.vm.marketCapFormatter('9', 0, item)).deep.equal('-');
        });

        it('value if market is not for token', () => {
            const wrapper = shallowMount(Trading, {
                mocks: {
                    $routing,
                },
                propsData: {
                    minimumVolumeForMarketcap: 10,
                },
            });
            let item = {
                base: 'foo',
                monthVolume: 10,

            };
            expect(wrapper.vm.marketCapFormatter('10', 0, item)).deep.equal('10');
        });

        it('value if monthVolume not less than minimumVolumeForMarketcap', () => {
            const wrapper = shallowMount(Trading, {
                mocks: {
                    $routing,
                },
                propsData: {
                    minimumVolumeForMarketcap: 10,
                },
            });
            let item = {
                base: 'MINTME',
                monthVolume: 10,

            };
            expect(wrapper.vm.marketCapFormatter('9', 0, item)).deep.equal('9');
        });
    });

    describe('data field', () => {
        describe(':tokens', () => {
            context('when fetch markets from server', () => {
                const markets = JSON.stringify({
                    TOK000000000001WEB: ['tok1', 'WEB'],
                    TOK000000000002WEB: ['tok2', 'WEB'],
                    TOK000000000003BTC: ['WEB', 'BTC'],
                });
                const vm = mount(Trading, {
                    propsData: {
                        marketNames: markets,
                    },
                });

                it('should contain WEB/tok1', (done) => {
                    vm.wsResult = {
                        'method': 'state.update',
                        'params': [
                            'TOK000000000001WEB',
                            {
                                'period': 86400,
                                'last': '123',
                                'open': '456',
                                'close': '789',
                                'high': '0',
                                'low': '0',
                                'volume': '321',
                                'deal': '0',
                            },
                        ],
                        'id': null,
                    };

                    Vue.nextTick(() => {
                        Vue.nextTick(() => {
                            expect(vm.tokens).to.deep.equal([
                                {pair: 'WEB/tok1', change: '-73.03', lastPrice: '123.00', volume: '321.00'},
                            ]);
                            done();
                        });
                        done();
                    });
                });

                it('should contain WEB/BTC before WEB/tok1', (done) => {
                    vm.wsResult = {
                        'method': 'state.update',
                        'params': [
                            'TOK000000000003BTC',
                            {
                                'period': 86400,
                                'last': '12',
                                'open': '45',
                                'close': '78',
                                'high': '0',
                                'low': '0',
                                'volume': '32',
                                'deal': '0',
                            },
                        ],
                        'id': null,
                    };

                    Vue.nextTick(() => {
                        Vue.nextTick(() => {
                            expect(vm.tokens).to.deep.equal([
                                {pair: 'WEB/BTC', change: '-73.33', lastPrice: '12.00', volume: '32.00'},
                                {pair: 'WEB/tok1', change: '-73.03', lastPrice: '123.00', volume: '321.00'},
                            ]);
                            done();
                        });
                        done();
                    });
                });

                it('should contain WEB/tok2 before WEB/tok1', (done) => {
                    vm.wsResult = {
                        'method': 'state.update',
                        'params': [
                            'TOK000000000002WEB',
                            {
                                'period': 86400,
                                'last': '1230',
                                'open': '4560',
                                'close': '7890',
                                'high': '0',
                                'low': '0',
                                'volume': '3210',
                                'deal': '0',
                            },
                        ],
                        'id': null,
                    };

                    Vue.nextTick(() => {
                        Vue.nextTick(() => {
                            expect(vm.tokens).to.deep.equal([
                                {pair: 'WEB/BTC', change: '-73.33', lastPrice: '12.00', volume: '32.00'},
                                {pair: 'WEB/tok2', change: '-73.03', lastPrice: '1230.00', volume: '3210.00'},
                                {pair: 'WEB/tok1', change: '-73.03', lastPrice: '123.00', volume: '321.00'},
                            ]);
                            done();
                        });
                        done();
                    });
                });

                it('BTC/WEB pair should be always first after sort', (done) => {
                    vm.sanitizedMarketsOnTop = [
                        {pair: 'WEB/tok2', change: '-73.03', lastPrice: '100.00', volume: '3210.00'},
                        {pair: 'WEB/BTC', change: '-73.33', lastPrice: '150.00', volume: '32.00'},
                        {pair: 'WEB/tok1', change: '-73.03', lastPrice: '250.00', volume: '321.00'},
                    ];

                    const sanitizedMarketsAfterSort = [
                        {pair: 'WEB/BTC', change: '-73.33', lastPrice: '150.00', volume: '32.00'},
                        {pair: 'WEB/tok2', change: '-73.03', lastPrice: '250.00', volume: '3210.00'},
                        {pair: 'WEB/tok1', change: '-73.03', lastPrice: '100.00', volume: '321.00'},
                    ];
                    vm.sanitizedMarketsOnTop = sanitizedMarketsAfterSort;
                    expect(vm.sanitizedMarketsOnTop).to.deep.equal(sanitizedMarketsAfterSort);
                    done();
                });
            });
        });
    });
});
