/*eslint-disable*/
(function(global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory(require('echarts/lib/echarts'), require('echarts/lib/component/tooltip'), require('echarts/lib/component/legend'), require('echarts/lib/chart/bar'), require('echarts/lib/chart/line'), require('echarts/lib/chart/candlestick'), require('echarts/lib/component/visualMap'), require('echarts/lib/component/dataZoom')) :
        typeof define === 'function' && define.amd ? define(['echarts/lib/echarts', 'echarts/lib/component/tooltip', 'echarts/lib/component/legend', 'echarts/lib/chart/bar', 'echarts/lib/chart/line', 'echarts/lib/chart/candlestick', 'echarts/lib/component/visualMap', 'echarts/lib/component/dataZoom'], factory) :
            (global.VeCandle = factory(global.echarts));
}(this, (function(echartsLib) {
    'use strict';

    echartsLib = echartsLib && echartsLib.hasOwnProperty('default') ? echartsLib['default'] : echartsLib;

    let DEFAULT_THEME = {
        categoryAxis: {
            axisLine: {show: false},
            axisTick: {show: false},
            splitLine: {show: false},
        },
        valueAxis: {
            axisLine: {show: false},
        },
        line: {
            smooth: true,
        },
        grid: {
            containLabel: true,
            left: 10,
            right: 10,
        },
    };

    let DEFAULT_COLORS = ['#19d4ae', '#5ab1ef', '#fa6e86', '#ffb980', '#0067a6', '#c4b4e4', '#d87a80', '#9cbbff', '#d9d0c7', '#87a997', '#d49ea2', '#5b4947', '#7ba3a8'];

    let itemPoint = function itemPoint(color) {
        return ['<span style="', 'background-color:' + color + ';', 'display: inline-block;', 'width: 10px;', 'height: 10px;', 'border-radius: 50%;', 'margin-right:2px;', '"></span>'].join('');
    };

    let STATIC_PROPS = ['initOptions', 'loading', 'dataEmpty', 'judgeWidth', 'widthChangeDelay'];

    let ECHARTS_SETTINGS = ['grid', 'dataZoom', 'visualMap', 'toolbox', 'title', 'legend', 'xAxis', 'yAxis', 'radar', 'tooltip', 'axisPointer', 'brush', 'geo', 'timeline', 'graphic', 'series', 'backgroundColor', 'textStyle'];

    let ABBR = {
        th: 3,
        mi: 6,
        bi: 9,
        tr: 12,
    };

    let DEFAULT_OPTIONS = {
        zeroFormat: null,
        nullFormat: null,
        defaultFormat: '0,0',
        scalePercentBy100: true,
        abbrLabel: {
            th: 'k',
            mi: 'm',
            bi: 'b',
            tr: 't',
        },
    };

    let TRILLION = 1e12;
    let BILLION = 1e9;
    let MILLION = 1e6;
    let THOUSAND = 1e3;

    function numIsNaN(value) {
        return typeof value === 'number' && isNaN(value);
    }

    function toFixed(value, maxDecimals, roundingFunction, optionals) {
        let splitValue = value.toString().split('.');
        let minDecimals = maxDecimals - (optionals || 0);
        let boundedPrecision = splitValue.length === 2 ? Math.min(Math.max(splitValue[1].length, minDecimals), maxDecimals) : minDecimals;
        let power = Math.pow(10, boundedPrecision);
        let output = (roundingFunction(value + 'e+' + boundedPrecision) / power).toFixed(boundedPrecision);

        if (optionals > maxDecimals - boundedPrecision) {
            let optionalsRegExp = new RegExp('\\.?0{1,' + (optionals - (maxDecimals - boundedPrecision)) + '}$');
            output = output.replace(optionalsRegExp, '');
        }

        return output;
    }

    function numberToFormat(options, value, format, roundingFunction) {
        let abs = Math.abs(value);
        let negP = false;
        let optDec = false;
        let abbr = '';
        let decimal = '';
        let neg = false;
        let abbrForce = void 0;
        let signed = void 0;
        format = format || '';

        value = value || 0;

        if (~format.indexOf('(')) {
            negP = true;
            format = format.replace(/[(|)]/g, '');
        } else if (~format.indexOf('+') || ~format.indexOf('-')) {
            signed = ~format.indexOf('+') ? format.indexOf('+') : value < 0 ? format.indexOf('-') : -1;
            format = format.replace(/[+|-]/g, '');
        }
        if (~format.indexOf('a')) {
            abbrForce = format.match(/a(k|m|b|t)?/);

            abbrForce = abbrForce ? abbrForce[1] : false;

            if (~format.indexOf(' a')) {
                abbr = ' ';
            }
            format = format.replace(new RegExp(abbr + 'a[kmbt]?'), '');

            if (abs >= TRILLION && !abbrForce || abbrForce === 't') {
                abbr += options.abbrLabel.tr;
                value = value / TRILLION;
            } else if (abs < TRILLION && abs >= BILLION && !abbrForce || abbrForce === 'b') {
                abbr += options.abbrLabel.bi;
                value = value / BILLION;
            } else if (abs < BILLION && abs >= MILLION && !abbrForce || abbrForce === 'm') {
                abbr += options.abbrLabel.mi;
                value = value / MILLION;
            } else if (abs < MILLION && abs >= THOUSAND && !abbrForce || abbrForce === 'k') {
                abbr += options.abbrLabel.th;
                value = value / THOUSAND;
            }
        }
        if (~format.indexOf('[.]')) {
            optDec = true;
            format = format.replace('[.]', '.');
        }
        let int = value.toString().split('.')[0];
        let precision = format.split('.')[1];
        let thousands = format.indexOf(',');
        let leadingCount = (format.split('.')[0].split(',')[0].match(/0/g) || []).length;

        if (precision) {
            if (~precision.indexOf('[')) {
                precision = precision.replace(']', '');
                precision = precision.split('[');
                decimal = toFixed(value, precision[0].length + precision[1].length, roundingFunction, precision[1].length);
            } else {
                decimal = toFixed(value, precision.length, roundingFunction);
            }

            int = decimal.split('.')[0];
            decimal = ~decimal.indexOf('.') ? '.' + decimal.split('.')[1] : '';
            if (optDec && +decimal.slice(1) === 0) {
                decimal = '';
            }
        } else {
            int = toFixed(value, 0, roundingFunction);
        }
        if (abbr && !abbrForce && +int >= 1000 && abbr !== ABBR.trillion) {
            int = '' + +int / 1000;
            abbr = ABBR.million;
        }
        if (~int.indexOf('-')) {
            int = int.slice(1);
            neg = true;
        }
        if (int.length < leadingCount) {
            for (let i = leadingCount - int.length; i > 0; i--) {
                int = '0' + int;
            }
        }

        if (thousands > -1) {
            int = int.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1' + ',');
        }

        if (!format.indexOf('.')) {
            int = '';
        }

        let output = int + decimal + (abbr || '');

        if (negP) {
            output = (negP && neg ? '(' : '') + output + (negP && neg ? ')' : '');
        } else {
            if (signed >= 0) {
                output = signed === 0 ? (neg ? '-' : '+') + output : output + (neg ? '-' : '+');
            } else if (neg) {
                output = '-' + output;
            }
        }

        return output;
    }

    function extend(target, sub) {
        Object.keys(sub).forEach(function(key) {
            target[key] = sub[key];
        });
    }

    let numerifyPercent = {
        regexp: /%/,
        format: function format(value, formatType, roundingFunction, numerify) {
            let space = ~formatType.indexOf(' %') ? ' ' : '';
            let output = void 0;

            if (numerify.options.scalePercentBy100) {
                value = value * 100;
            }

            formatType = formatType.replace(/\s?%/, '');

            output = numerify._numberToFormat(value, formatType, roundingFunction);

            if (~output.indexOf(')')) {
                output = output.split('');
                output.splice(-1, 0, space + '%');
                output = output.join('');
            } else {
                output = output + space + '%';
            }

            return output;
        },
    };

    let options = {};
    let formats = {};

    extend(options, DEFAULT_OPTIONS);

    function format(value, formatType, roundingFunction) {
        formatType = formatType || options.defaultFormat;
        roundingFunction = roundingFunction || Math.round;
        let output = void 0;
        let formatFunction = void 0;

        if (value === 0 && options.zeroFormat !== null) {
            output = options.zeroFormat;
        } else if (value === null && options.nullFormat !== null) {
            output = options.nullFormat;
        } else {
            for (let kind in formats) {
                if (formats[kind] && formatType.match(formats[kind].regexp)) {
                    formatFunction = formats[kind].format;
                    break;
                }
            }
            formatFunction = formatFunction || numberToFormat.bind(null, options);
            output = formatFunction(value, formatType, roundingFunction, numerify);
        }

        return output;
    }

    function numerify(input, formatType, roundingFunction) {
        let value = void 0;

        if (input === 0 || typeof input === 'undefined') {
            value = 0;
        } else if (input === null || numIsNaN(input)) {
            value = null;
        } else if (typeof input === 'string') {
            if (options.zeroFormat && input === options.zeroFormat) {
                value = 0;
            } else if (options.nullFormat && input === options.nullFormat || !input.replace(/[^0-9]+/g, '').length) {
                value = null;
            } else {
                value = +input;
            }
        } else {
            value = +input || null;
        }

        return format(value, formatType, roundingFunction);
    }

    numerify.options = options;
    numerify._numberToFormat = numberToFormat.bind(null, options);
    numerify.register = function(name, format) {
        formats[name] = format;
    };
    numerify.unregister = function(name) {
        formats[name] = null;
    };
    numerify.setOptions = function(opts) {
        extend(options, opts);
    };
    numerify.reset = function() {
        extend(options, DEFAULT_OPTIONS);
    };

    numerify.register('percentage', numerifyPercent);

    let _typeof = typeof Symbol === 'function' && typeof Symbol.iterator === 'symbol' ? function(obj) {
        return typeof obj;
    } : function(obj) {
        return obj && typeof Symbol === 'function' && obj.constructor === Symbol && obj !== Symbol.prototype ? 'symbol' : typeof obj;
    };

    let _extends = Object.assign || function(target) {
        for (let i = 1; i < arguments.length; i++) {
            let source = arguments[i];

            for (let key in source) {
                if (Object.prototype.hasOwnProperty.call(source, key)) {
                    target[key] = source[key];
                }
            }
        }

        return target;
    };

    function debounce(fn, delay) {
        let timer = null;
        return function() {
            let self = this;
            let args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(self, args);
            }, delay);
        };
    }

    function set$1(target, path, value) {
        if (!path) {
            return;
        }
        let targetTemp = target;
        let pathArr = path.split('.');
        pathArr.forEach(function(item, index) {
            if (index === pathArr.length - 1) {
                targetTemp[item] = value;
            } else {
                if (!targetTemp[item]) {
                    targetTemp[item] = {};
                }
                targetTemp = targetTemp[item];
            }
        });
    }

    let _typeof$1 = typeof Symbol === 'function' && _typeof(Symbol.iterator) === 'symbol' ? function(obj) {
        return typeof obj === 'undefined' ? 'undefined' : _typeof(obj);
    } : function(obj) {
        return obj && typeof Symbol === 'function' && obj.constructor === Symbol && obj !== Symbol.prototype ? 'symbol' : typeof obj === 'undefined' ? 'undefined' : _typeof(obj);
    };

    function getType(v) {
        return Object.prototype.toString.call(v);
    }

    function getTypeof(v) {
        return typeof v === 'undefined' ? 'undefined' : _typeof$1(v);
    }

    function isObject(v) {
        return getType(v) === '[object Object]';
    }

    function isArray(v) {
        return getType(v) === '[object Array]';
    }

    function isFunction(v) {
        return getType(v) === '[object Function]';
    }

    function cloneDeep(v) {
        return JSON.parse(JSON.stringify(v));
    }

    function camelToKebab(s) {
        return s.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
    }

    function hasOwn(source, target) {
        return Object.prototype.hasOwnProperty.call(source, target);
    }

    function isEqual(alice, bob) {
        if (alice === bob) {
            return true;
        }
        if (alice === null || bob === null || getTypeof(alice) !== 'object' || getTypeof(bob) !== 'object') {
            return alice === bob;
        }

        for (let key in alice) {
            if (!hasOwn(alice, key)) {
                continue;
            }
            let aliceValue = alice[key];
            let bobValue = bob[key];
            let aliceType = getTypeof(aliceValue);

            if (getTypeof(bobValue) === 'undefined') {
                return false;
            } else if (aliceType === 'object') {
                if (!isEqual(aliceValue, bobValue)) {
                    return false;
                }
            } else if (aliceValue !== bobValue) {
                return false;
            }
        }
        for (let _key in bob) {
            if (!hasOwn(bob, _key)) {
                continue;
            }
            if (getTypeof(alice)[_key] === 'undefined') {
                return false;
            }
        }

        return true;
    }

    let getFormated = function getFormated(val, type, digit) {
        let defaultVal = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : '-';

        if (isNaN(val)) {
            return defaultVal;
        }
        if (!type) {
            return val;
        }
        if (isFunction(type)) {
            return type(val, numerify);
        }

        digit = isNaN(digit) ? 0 : ++digit;
        let digitStr = '.[' + new Array(digit).join(0) + ']';
        let formatter = type;
        switch (type) {
            case 'KMB':
                formatter = digit ? '0,0' + digitStr + 'a' : '0,0a';
                break;
            case 'normal':
                formatter = digit ? '0,0' + digitStr : '0,0';
                break;
            case 'percent':
                formatter = digit ? '0,0' + digitStr + '%' : '0,0.[00]%';
                break;
        }
        return numerify(val, formatter);
    };

    let DEFAULT_MA = [5, 10, 20, 30];
    let DEFAULT_K_NAME = '日K';
    let DEFAULT_DOWN_COLOR = '#ec0000';
    let DEFAULT_UP_COLOR = '#00da3c';
    let DEFAULT_START = 50;
    let DEFAULT_END = 100;
    let SHOW_FALSE = {show: false};

    function getCandleLegend(args) {
        let showMA = args.showMA;
        let MA = args.MA;
        let legendName = args.legendName;
        let labelMap = args.labelMap;

        let data = [DEFAULT_K_NAME];
        if (showMA) {
            data = data.concat(MA.map(function(v) {
                return 'MA' + v;
            }));
        }
        if (labelMap) {
            data = data.map(function(v) {
                return labelMap[v] == null ? v : labelMap[v];
            });
        }
        return {
            data: data,
            formatter: function formatter(name) {
                return legendName[name] != null ? legendName[name] : name;
            },
        };
    }

    function getCandleTooltip(args) {
        let metrics = args.metrics;
        let dataType = args.dataType;
        let digit = args.digit;
        let labelMap = args.labelMap;

        return {
            trigger: 'axis',
            axisPointer: {type: 'cross'},
            position: function position(pos, params, el, elRect, size) {
                let result = {top: 10};
                let side = pos[0] < size.viewSize[0] / 2 ? 'right' : 'left';
                result[side] = 60;
                return result;
            },
            formatter: function formatter(options) {
                let tpl = [];
                tpl.push(options[0].axisValue + '<br>');
                options.forEach(function(option) {
                    let data = option.data;

                    if (option.seriesName !== (labelMap[DEFAULT_K_NAME] == null ? DEFAULT_K_NAME : labelMap[DEFAULT_K_NAME])) {
                        return;
                    }

                    let seriesName = option.seriesName;
                    let componentSubType = option.componentSubType;
                    let color = option.color;

                    let name = labelMap[seriesName] == null ? seriesName : labelMap[seriesName];
                    tpl.push(itemPoint(color) + ' ' + name + ': ');
                    if (componentSubType === 'candlestick') {
                        tpl.push('<br>');
                        metrics.slice(0, 4).forEach(function(m, i) {
                            let name = labelMap[m] != null ? labelMap[m] : m;
                            let val = getFormated(data[i + 1], dataType, digit);
                            tpl.push('- ' + name + ': ' + val + '<br>');
                        });
                    } else if (componentSubType === 'line') {
                        let val = getFormated(data, dataType, digit);
                        tpl.push(val + '<br>');
                    } else if (componentSubType === 'bar') {
                        let _val = getFormated(data[1], dataType, digit);
                        tpl.push(_val + '<br>');
                    }
                });
                return tpl.join('');
            },
        };
    }

    function getCandleVisualMap(args) {
        let downColor = args.downColor;
        let upColor = args.upColor;
        let MA = args.MA;
        let showMA = args.showMA;

        return {
            show: false,
            seriesIndex: showMA ? 1 + MA.length : 1,
            dimension: 2,
            pieces: [{value: 1, color: downColor}, {value: -1, color: upColor}],
        };
    }

    function getCandleGrid(args) {
        let showVol = args.showVol;

        return [{
            left: '10%',
            right: '8%',
            top: '10%',
            height: showVol ? '50%' : '65%',
            containLabel: false,
        }, {
            left: '10%',
            right: '8%',
            top: '65%',
            height: '16%',
            containLabel: false,
        }];
    }

    function getCandleXAxis(args) {
        let data = args.dims;

        let type = 'category';
        let scale = true;
        let boundaryGap = false;
        let splitLine = SHOW_FALSE;
        let axisLine = {onZero: false};
        let axisTick = SHOW_FALSE;
        let axisLabel = SHOW_FALSE;
        let min = 'dataMin';
        let max = 'dataMax';
        let gridIndex = 1;
        let axisPointer = {label: SHOW_FALSE};

        return [{
            type: type,
            data: data,
            scale: scale,
            boundaryGap: boundaryGap,
            axisLine: axisLine,
            splitLine: splitLine,
            min: min,
            max: max,
        }, {
            type: type,
            gridIndex: gridIndex,
            data: data,
            scale: scale,
            boundaryGap: boundaryGap,
            axisLine: axisLine,
            axisTick: axisTick,
            splitLine: splitLine,
            axisLabel: axisLabel,
            min: min,
            max: max,
            axisPointer: axisPointer,
        }];
    }

    function getCandleYAxis(args) {
        let dataType = args.dataType;
        let digit = args.digit;

        let scale = true;
        let gridIndex = 1;
        let splitNumber = 2;
        let axisLine = SHOW_FALSE;
        let axisTick = SHOW_FALSE;
        let axisLabel = SHOW_FALSE;
        let splitLine = SHOW_FALSE;
        let formatter = function formatter(val) {
            return getFormated(val, dataType, digit);
        };

        return [
            {position: 'left', scale: scale, axisTick: axisTick, axisLabel: {formatter: formatter}},
            {position: 'right', scale: scale, axisTick: axisTick, axisLabel: {formatter: formatter}},
            {
                scale: scale,
                gridIndex: gridIndex,
                splitNumber: splitNumber,
                axisLine: axisLine,
                axisTick: axisTick,
                splitLine: splitLine,
                axisLabel: axisLabel,
            },
        ];
    }

    function getCandleDataZoom(args) {
        let start = args.start;
        let end = args.end;


        return [{
            type: 'inside',
            xAxisIndex: [0, 1],
            start: start,
            end: end,
        }, {
            show: true,
            xAxisIndex: [0, 1],
            type: 'slider',
            top: '85%',
            start: start,
            end: end,
        }];
    }

    function getCandleSeries(args) {
        let values = args.values;
        let volumes = args.volumes;
        let upColor = args.upColor;
        let downColor = args.downColor;
        let showMA = args.showMA;
        let MA = args.MA;
        let showVol = args.showVol;
        let labelMap = args.labelMap;
        let digit = args.digit;
        let itemStyle = args.itemStyle;

        let style = itemStyle || {
            normal: {
                color: upColor,
                color0: downColor,
                borderColor: null,
                borderColor0: null,
            },
        };
        let lineStyle = {normal: {opacity: 0.5}};
        let rightAxis = args.rightLabel
            ? [{
                name: 'extraAxis',
                type: 'candlestick',
                yAxisIndex: 1,
                data: values,
                itemStyle: style,
            }]
            : [];
        let series = [
            {
                name: labelMap[DEFAULT_K_NAME] == null ? DEFAULT_K_NAME : labelMap[DEFAULT_K_NAME],
                type: 'candlestick',
                data: values,
                itemStyle: style,
            },
            ...rightAxis,
        ];

        if (showMA) {
            MA.forEach(function(d) {
                let name = 'MA' + d;
                series.push({
                    name: labelMap[name] == null ? name : labelMap[name],
                    data: calculateMA(d, values, digit),
                    type: 'line',
                    lineStyle: lineStyle,
                    smooth: true,
                });
            });
        }

        if (showVol) {
            series.push({
                name: 'Volume',
                type: 'bar',
                xAxisIndex: 1,
                yAxisIndex: 1,
                data: volumes,
            });
        }

        return series;
    }

    function calculateMA(dayCount, data, digit) {
        let result = [];
        data.forEach(function(d, i) {
            if (i < dayCount) {
                result.push('-');
            } else {
                let sum = 0;
                for (let j = 0; j < dayCount; j++) {
                    sum += data[i - j][1];
                }
                result.push(+(sum / dayCount).toFixed(digit));
            }
        });
        return result;
    }

    let candle = function candle(columns, rows, settings, status, rightLabel) {
        let _settings$dimension = settings.dimension;
        let dimension = _settings$dimension === undefined ? columns[0] : _settings$dimension;
        let _settings$metrics = settings.metrics;
        let metrics = _settings$metrics === undefined ? columns.slice(1, 6) : _settings$metrics;
        let _settings$digit = settings.digit;
        let digit = _settings$digit === undefined ? 2 : _settings$digit;
        let itemStyle = settings.itemStyle;
        let _settings$labelMap = settings.labelMap;
        let labelMap = _settings$labelMap === undefined ? {} : _settings$labelMap;
        let _settings$legendName = settings.legendName;
        let legendName = _settings$legendName === undefined ? {} : _settings$legendName;
        let _settings$MA = settings.MA;
        let MA = _settings$MA === undefined ? DEFAULT_MA : _settings$MA;
        let _settings$showMA = settings.showMA;
        let showMA = _settings$showMA === undefined ? false : _settings$showMA;
        let _settings$showVol = settings.showVol;
        let showVol = _settings$showVol === undefined ? false : _settings$showVol;
        let _settings$showDataZoo = settings.showDataZoom;
        let showDataZoom = _settings$showDataZoo === undefined ? false : _settings$showDataZoo;
        let _settings$downColor = settings.downColor;
        let downColor = _settings$downColor === undefined ? DEFAULT_DOWN_COLOR : _settings$downColor;
        let _settings$upColor = settings.upColor;
        let upColor = _settings$upColor === undefined ? DEFAULT_UP_COLOR : _settings$upColor;
        let _settings$start = settings.start;
        let start = _settings$start === undefined ? DEFAULT_START : _settings$start;
        let _settings$end = settings.end;
        let end = _settings$end === undefined ? DEFAULT_END : _settings$end;
        let dataType = settings.dataType;
        let tooltipVisible = status.tooltipVisible;
        let legendVisible = status.legendVisible;


        let isLiteData = isArray(rows[0]);
        let dims = [];
        let values = [];
        let volumes = [];
        let candleMetrics = metrics.slice(0, 4);
        let volumeMetrics = metrics[4];

        if (isLiteData) {
            rows.forEach(function(row) {
                let itemResult = [];
                dims.push(row[columns.indexOf(dimension)]);
                candleMetrics.forEach(function(item) {
                    itemResult.push(row[columns.indexOf(item)]);
                });
                values.push(itemResult);
                if (volumeMetrics) {
                    volumes.push(row[columns.indexOf(volumeMetrics)]);
                }
            });
        } else {
            rows.forEach(function(row, index) {
                let itemResult = [];
                dims.push(row[dimension]);
                candleMetrics.forEach(function(item) {
                    itemResult.push(row[item]);
                });
                values.push(itemResult);
                if (volumeMetrics) {
                    let _status = row[metrics[0]] > row[metrics[1]] ? 1 : -1;
                    volumes.push([index, row[volumeMetrics], _status]);
                }
            });
        }

        let legend$$1 = legendVisible && getCandleLegend({
            showMA: showMA,
            MA: MA,
            legendName: legendName,
            labelMap: labelMap,
        });
        let tooltip$$1 = tooltipVisible && getCandleTooltip({
            metrics: metrics,
            dataType: dataType,
            digit: digit,
            labelMap: labelMap,
        });
        let visualMap$$1 = showVol && getCandleVisualMap({
            downColor: downColor,
            upColor: upColor,
            MA: MA,
            showMA: showMA,
        });
        let dataZoom$$1 = showDataZoom && getCandleDataZoom({start: start, end: end});
        let grid = getCandleGrid({showVol: showVol});
        let xAxis = getCandleXAxis({dims: dims});
        let yAxis = getCandleYAxis({dataType: dataType, digit: digit});
        let series = getCandleSeries({
            rightLabel: rightLabel,
            values: values,
            volumes: volumes,
            upColor: upColor,
            downColor: downColor,
            showMA: showMA,
            MA: MA,
            showVol: showVol,
            labelMap: labelMap,
            digit: digit,
            itemStyle: itemStyle,
        });
        return {
            legend: legend$$1,
            tooltip: tooltip$$1,
            visualMap: visualMap$$1,
            grid: grid,
            xAxis: xAxis,
            yAxis: yAxis,
            dataZoom: dataZoom$$1,
            series: series,
        };
    };

    let Loading = {
        render: function render() {
            let _vm = this;
            let _h = _vm.$createElement;
            let _c = _vm._self._c || _h;
            return _c('div', {staticClass: 'v-charts-component-loading'}, [_c('div', {staticClass: 'loader'}, [_c('div', {staticClass: 'loading-spinner'}, [_c('svg', {
                staticClass: 'circular',
                attrs: {'viewBox': '25 25 50 50'},
            }, [_c('circle', {staticClass: 'path', attrs: {'cx': '50', 'cy': '50', 'r': '20', 'fill': 'none'}})])])])]);
        }, staticRenderFns: [],
    };

    let DataEmpty = {
        render: function render() {
            let _vm = this;
            let _h = _vm.$createElement;
            let _c = _vm._self._c || _h;
            return _c('div', {staticClass: 'v-charts-data-empty'}, [_vm._v(' 暂无数据 ')]);
        }, staticRenderFns: [],
    };

    function setExtend(options, extend) {
        Object.keys(extend).forEach(function(attr) {
            let value = extend[attr];
            if (~attr.indexOf('.')) {
                // eg: a.b.c a.1.b
                set$1(options, attr, value);
            } else if (typeof value === 'function') {
                // get callback value
                options[attr] = value(options[attr]);
            } else {
                // mixin extend value
                if (isArray(options[attr]) && isObject(options[attr][0])) {
                    // eg: [{ xx: 1 }, { xx: 2 }]
                    options[attr].forEach(function(option, index) {
                        options[attr][index] = _extends({}, option, value);
                    });
                } else if (isObject(options[attr])) {
                    // eg: { xx: 1, yy: 2 }
                    options[attr] = _extends({}, options[attr], value);
                } else {
                    options[attr] = value;
                }
            }
        });
    }

    function setMark(seriesItem, marks) {
        Object.keys(marks).forEach(function(key) {
            if (marks[key]) {
                seriesItem[key] = marks[key];
            }
        });
    }

    function setAnimation(options, animation) {
        Object.keys(animation).forEach(function(key) {
            options[key] = animation[key];
        });
    }

    let Core = {
        render: function render(h) {
            return h('div', {
                class: [camelToKebab(this.$options.name || this.$options._componentTag)],
                style: this.canvasStyle,
            }, [h('div', {
                style: this.canvasStyle,
                class: {'v-charts-mask-status': this.dataEmpty || this.loading},
                ref: 'canvas',
            }), h(DataEmpty, {
                style: {display: this.dataEmpty ? '' : 'none'},
            }), h(Loading, {
                style: {display: this.loading ? '' : 'none'},
            }), this.$slots.default]);
        },


        props: {
            data: {
                type: [Object, Array], default: function _default() {
                    return {};
                },
            },
            settings: {
                type: Object, default: function _default() {
                    return {};
                },
            },
            width: {type: String, default: 'auto'},
            height: {type: String, default: '400px'},
            beforeConfig: {type: Function},
            afterConfig: {type: Function},
            afterSetOption: {type: Function},
            afterSetOptionOnce: {type: Function},
            events: {type: Object},
            grid: {type: [Object, Array]},
            colors: {type: Array},
            tooltipVisible: {type: Boolean, default: true},
            legendVisible: {type: Boolean, default: true},
            legendPosition: {type: String},
            markLine: {type: Object},
            markArea: {type: Object},
            markPoint: {type: Object},
            visualMap: {type: [Object, Array]},
            dataZoom: {type: [Object, Array]},
            toolbox: {type: [Object, Array]},
            initOptions: {
                type: Object, default: function _default() {
                    return {};
                },
            },
            title: [Object, Array],
            legend: [Object, Array],
            xAxis: [Object, Array],
            yAxis: [Object, Array],
            radar: Object,
            tooltip: Object,
            axisPointer: [Object, Array],
            brush: [Object, Array],
            geo: [Object, Array],
            timeline: [Object, Array],
            graphic: [Object, Array],
            series: [Object, Array],
            backgroundColor: [Object, String],
            textStyle: [Object, Array],
            animation: Object,
            theme: Object,
            themeName: String,
            loading: Boolean,
            dataEmpty: Boolean,
            extend: Object,
            rightLabel: Boolean,
            judgeWidth: {type: Boolean, default: false},
            widthChangeDelay: {type: Number, default: 300},
            tooltipFormatter: {type: Function},
            resizeable: {type: Boolean, default: true},
            resizeDelay: {type: Number, default: 200},
            changeDelay: {type: Number, default: 0},
            setOptionOpts: {type: [Boolean, Object], default: true},
            cancelResizeCheck: Boolean,
            notSetUnchange: Array,
            log: Boolean,
        },

        watch: {
            data: {
                deep: true,
                handler: function handler(v) {
                    if (v) {
                        this.changeHandler();
                    }
                },
            },

            settings: {
                deep: true,
                handler: function handler(v) {
                    if (v.type && this.chartLib) {
                        this.chartHandler = this.chartLib[v.type];
                    }
                    this.changeHandler();
                },
            },

            width: 'nextTickResize',
            height: 'nextTickResize',

            events: {
                deep: true,
                handler: 'createEventProxy',
            },

            theme: {
                deep: true,
                handler: 'themeChange',
            },

            themeName: 'themeChange',

            resizeable: 'resizeableHandler',
        },

        computed: {
            canvasStyle: function canvasStyle() {
                return {
                    width: this.width,
                    height: this.height,
                    position: 'relative',
                };
            },
            chartColor: function chartColor() {
                return this.colors || this.theme && this.theme.color || DEFAULT_COLORS;
            },
        },

        methods: {
            mouseOutHandler: function mouseOutHandler() {
                const canvasDiv = this.$refs.canvas.childNodes[0];
                const mouseOverHandler = function() {
                    const tooltip = canvasDiv.nextElementSibling;
                    canvasDiv.removeEventListener('mouseover', mouseOverHandler);
                    canvasDiv.addEventListener('mouseout', function() {
                        setTimeout(function() {
                            tooltip.style.display = 'none';
                        }, 300);
                    });
                };
                canvasDiv.addEventListener('mouseover', mouseOverHandler);
            },
            dataHandler: function dataHandler() {
                if (!this.chartHandler) {
                    return;
                }
                let data = this.data;
                let _data = data;
                let _data$columns = _data.columns;
                let columns = _data$columns === undefined ? [] : _data$columns;
                let _data$rows = _data.rows;
                let rows = _data$rows === undefined ? [] : _data$rows;

                let extra = {
                    tooltipVisible: this.tooltipVisible,
                    legendVisible: this.legendVisible,
                    echarts: this.echarts,
                    color: this.chartColor,
                    tooltipFormatter: this.tooltipFormatter,
                    _once: this._once,
                };
                if (this.beforeConfig) {
                    data = this.beforeConfig(data);
                }

                let options = this.chartHandler(columns, rows, this.settings, extra, this.rightLabel);
                if (options) {
                    if (typeof options.then === 'function') {
                        options.then(this.optionsHandler);
                    } else {
                        this.optionsHandler(options);
                    }
                }
                this.mouseOutHandler();
            },
            nextTickResize: function nextTickResize() {
                this.$nextTick(this.resize);
            },
            resize: function resize() {
                if (!this.cancelResizeCheck) {
                    if (this.$el && this.$el.clientWidth && this.$el.clientHeight) {
                        this.echartsResize();
                    }
                } else {
                    this.echartsResize();
                }
            },
            echartsResize: function echartsResize() {
                this.echarts && this.echarts.resize();
            },
            optionsHandler: function optionsHandler(options) {
                let _this = this;

                // legend
                if (this.legendPosition && options.legend) {
                    options.legend[this.legendPosition] = 10;
                    if (~['left', 'right'].indexOf(this.legendPosition)) {
                        options.legend.top = 'middle';
                        options.legend.orient = 'vertical';
                    }
                }
                // color
                options.color = this.chartColor;
                // echarts self settings
                ECHARTS_SETTINGS.forEach(function(setting) {
                    if (_this[setting]) {
                        options[setting] = _this[setting];
                    }
                });
                // animation
                if (this.animation) {
                    setAnimation(options, this.animation);
                }
                // marks
                if (this.markArea || this.markLine || this.markPoint) {
                    let marks = {
                        markArea: this.markArea,
                        markLine: this.markLine,
                        markPoint: this.markPoint,
                    };
                    let series = options.series;
                    if (isArray(series)) {
                        series.forEach(function(item) {
                            setMark(item, marks);
                        });
                    } else if (isObject(series)) {
                        setMark(series, marks);
                    }
                }
                // change inited echarts settings
                if (this.extend) {
                    setExtend(options, this.extend);
                }
                if (this.afterConfig) {
                    options = this.afterConfig(options);
                }
                let setOptionOpts = this.setOptionOpts;
                // map chart not merge
                if ((this.settings.bmap || this.settings.amap) && !isObject(setOptionOpts)) {
                    setOptionOpts = false;
                }
                // exclude unchange options
                if (this.notSetUnchange && this.notSetUnchange.length) {
                    this.notSetUnchange.forEach(function(item) {
                        let value = options[item];
                        if (value) {
                            if (isEqual(value, _this._store[item])) {
                                options[item] = undefined;
                            } else {
                                _this._store[item] = cloneDeep(value);
                            }
                        }
                    });
                    if (isObject(setOptionOpts)) {
                        setOptionOpts.notMerge = false;
                    } else {
                        setOptionOpts = false;
                    }
                }
                if (this._isDestroyed) {
                    return;
                }
                if (this.log) {
                    console.log(options);
                }
                this.echarts.setOption(options, setOptionOpts);
                this.$emit('ready', this.echarts, options, echartsLib);
                if (!this._once['ready-once']) {
                    this._once['ready-once'] = true;
                    this.$emit('ready-once', this.echarts, options, echartsLib);
                }
                if (this.judgeWidth) {
                    this.judgeWidthHandler(options);
                }
                if (this.afterSetOption) {
                    this.afterSetOption(this.echarts, options, echartsLib);
                }
                if (this.afterSetOptionOnce && !this._once['afterSetOptionOnce']) {
                    this._once['afterSetOptionOnce'] = true;
                    this.afterSetOptionOnce(this.echarts, options, echartsLib);
                }
            },
            judgeWidthHandler: function judgeWidthHandler(options) {
                let _this2 = this;

                let widthChangeDelay = this.widthChangeDelay;
                let resize = this.resize;

                if (this.$el.clientWidth || this.$el.clientHeight) {
                    resize();
                } else {
                    this.$nextTick(function(_) {
                        if (_this2.$el.clientWidth || _this2.$el.clientHeight) {
                            resize();
                        } else {
                            setTimeout(function(_) {
                                resize();
                                if (!_this2.$el.clientWidth || !_this2.$el.clientHeight) {
                                    console.warn(' Can\'t get dom width or height ');
                                }
                            }, widthChangeDelay);
                        }
                    });
                }
            },
            resizeableHandler: function resizeableHandler(resizeable) {
                if (resizeable && !this._once.onresize) {
                    this.addResizeListener();
                }
                if (!resizeable && this._once.onresize) {
                    this.removeResizeListener();
                }
            },
            init: function init() {
                if (this.echarts) {
                    return;
                }
                let themeName = this.themeName || this.theme || DEFAULT_THEME;
                this.echarts = echartsLib.init(this.$refs.canvas, themeName, this.initOptions);
                if (this.data) {
                    this.changeHandler();
                }
                this.createEventProxy();
                if (this.resizeable) {
                    this.addResizeListener();
                }
            },
            addResizeListener: function addResizeListener() {
                window.addEventListener('resize', this.resizeHandler);
                this._once.onresize = true;
            },
            removeResizeListener: function removeResizeListener() {
                window.removeEventListener('resize', this.resizeHandler);
                this._once.onresize = false;
            },
            addWatchToProps: function addWatchToProps() {
                let _this3 = this;

                let watchedVariable = this._watchers.map(function(watcher) {
                    return watcher.expression;
                });
                Object.keys(this.$props).forEach(function(prop) {
                    if (!~watchedVariable.indexOf(prop) && !~STATIC_PROPS.indexOf(prop)) {
                        let opts = {};
                        if (~['[object Object]', '[object Array]'].indexOf(getType(_this3.$props[prop]))) {
                            opts.deep = true;
                        }
                        _this3.$watch(prop, function() {
                            _this3.changeHandler();
                        }, opts);
                    }
                });
            },
            createEventProxy: function createEventProxy() {
                let _this4 = this;

                // 只要用户使用 on 方法绑定的事件都做一层代理，
                // 是否真正执行相应的事件方法取决于该方法是否仍然存在 events 中
                // 实现 events 的动态响应
                let self = this;
                let keys = Object.keys(this.events || {});
                keys.length && keys.forEach(function(ev) {
                    if (_this4.registeredEvents.indexOf(ev) === -1) {
                        _this4.registeredEvents.push(ev);
                        _this4.echarts.on(ev, function(ev) {
                            return function() {
                                if (ev in self.events) {
                                    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
                                        args[_key] = arguments[_key];
                                    }

                                    self.events[ev].apply(null, args);
                                }
                            };
                        }(ev));
                    }
                });
            },
            themeChange: function themeChange(theme) {
                this.clean();
                this.echarts = null;
                this.init();
            },
            clean: function clean() {
                if (this.resizeable) {
                    this.removeResizeListener();
                }
                this.echarts.dispose();
            },
        },

        created: function created() {
            this.echarts = null;
            this.registeredEvents = [];
            this._once = {};
            this._store = {};
            this.resizeHandler = debounce(this.resize, this.resizeDelay);
            this.changeHandler = debounce(this.dataHandler, this.changeDelay);
            this.addWatchToProps();
        },
        mounted: function mounted() {
            this.init();
        },
        beforeDestroy: function beforeDestroy() {
            this.clean();
        },


        _numerify: numerify,
    };

    let index = _extends({}, Core, {
        name: 'VeCandle',
        data: function data() {
            this.chartHandler = candle;
            return {};
        },
    });

    return index;
})));
