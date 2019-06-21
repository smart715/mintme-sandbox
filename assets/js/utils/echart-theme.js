/* eslint-disable valid-jsdoc */
/**
 * @param {Number} precision
 * @return Object
 */
export default function(precision) {
    return {
        backgroundColor: 'transparent',

        color: [
            '#ff7f50', '#87cefa', '#da70d6', '#32cd32', '#6495ed',
            '#ff69b4', '#ba55d3', '#cd5c5c', '#ffa500', '#40e0d0',
            '#1e90ff', '#ff6347', '#7b68ee', '#00fa9a', '#ffd700',
            '#6699FF', '#ff6666', '#3cb371', '#b8860b', '#30e0e0',
        ],

        legend: {
            show: false,
            textStyle: {
                color: '#fff',
            },
            top: 0,
        },

        tooltip: {
            trigger: 'item',
            showDelay: 20,
            hideDelay: 100,
            transitionDuration: 0.4,
            backgroundColor: 'rgba(0,0,0,0.7)',
            borderColor: '#333',
            borderRadius: 4,
            borderWidth: 0,
            padding: 5,
            axisPointer: {
                type: 'line',
                lineStyle: {
                    color: 'rgba(0,0,0,0)',
                    width: 1,
                    type: 'solid',
                },
                shadowStyle: {
                    width: 'auto',
                    color: 'rgba(150,150,150,0.3)',
                },
            },
            textStyle: {
                color: '#fff',
            },
        },

        dataZoom: {
            orient: 'horizontal',
            fillerColor: 'rgba(0,89,127,0.3)',
            handleStyle: {
                color: '#00597F',
                opacity: 0.5,
            },
            borderColor: 'rgba(0,0,0,0)',
            dataBackground: {
                areaStyle: {
                    color: '#e6e6e6',
                    opacity: 1,
                },
            },
            backgroundColor: '#768897',
            textStyle: {
                color: '#0083af',
            },
            realtime: true,
        },

        axisPointer: {
            link: {xAxisIndex: 'all'},
            label: {
                backgroundColor: 'rgba(0,0,0,0.7)',
                showMinLabel: false,
                precision: precision,
            },
        },

        categoryAxis: {
            position: 'bottom',
            nameLocation: 'end',
            boundaryGap: true,
            axisLine: {
                show: false,
            },
            axisTick: {
                show: true,
                interval: 'auto',
                inside: false,
                length: 5,
                lineStyle: {
                    color: ['#fff'],
                    width: 1,
                },
                alignWithLabel: true,
            },
            axisLabel: {
                show: true,
                interval: 'auto',
                rotate: 0,
                margin: 8,

                textStyle: {
                    color: '#fff',
                },
            },
            splitLine: {
                show: true,

                lineStyle: {
                    width: 1,
                    type: 'solid',
                },
            },
            splitArea: {
                show: false,
            },
        },


        valueAxis: {
            position: 'left',
            nameLocation: 'end',
            nameTextStyle: {},
            boundaryGap: [0, 0],
            splitNumber: 5,
            axisLine: {
                show: true,
                lineStyle: {
                    color: '#fff',
                    width: 1,
                    type: 'solid',
                },
            },
            axisTick: {
                show: false,
            },
            axisLabel: {
                show: true,
                rotate: 0,
                margin: 8,
                textStyle: {
                    color: '#fff',
                },
            },
            splitLine: {
                show: true,
                lineStyle: {
                    color: ['#fff'],
                    width: 1,
                    type: 'solid',
                },
            },
            splitArea: {
                show: false,
            },
        },

        textStyle: {
            decoration: 'none',
            fontFamily: 'Arial, Verdana, sans-serif',
            fontFamily2: '微软雅黑',
            fontSize: 12,
            fontStyle: 'normal',
            fontWeight: 'normal',
        },


        symbolList: [
            'circle', 'rectangle', 'triangle', 'diamond',
            'emptyCircle', 'emptyRectangle', 'emptyTriangle', 'emptyDiamond',
        ],
        loadingText: 'Loading...',

        calculable: false,
        calculableColor: 'rgba(255,165,0,0.6)',
        calculableHolderColor: '#ccc',
        nameConnector: ' & ',
        valueConnector: ' : ',
        animation: true,
        animationThreshold: 2500,
        addDataAnimation: true,
        animationDuration: 2000,
        animationEasing: 'ExponentialOut',
    };
};
