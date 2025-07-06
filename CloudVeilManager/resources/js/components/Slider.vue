<script>
import noUiSlider from "nouislider";
import "../../css/nouislider.css";
export default {
    props: {
        min: {
            type: Number,
            default: 0
        },
        max: {
            type: Number,
            default: 1439
        },
        initialIntervals: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            options: {
                start: ["00:00", "23:59"],
                connect: [false, true, false],
                range: {
                    'min': [this.min],
                    'max': [this.max]
                },
                format: {
                    from: this.formatFrom,
                    to: this.formatTo
                },
                step: 10,
                tooltips: true,
                pips: {
                    mode: 'positions',
                    values: [0, 25, 50, 75, 100],
                    density: 10,
                    format: {
                        to: this.formatTo
                    },
                }
            },

        }
    },
    mounted() {
        let sliderIntervals = [];
        let initIntervalsJSON = JSON.parse(this.initialIntervals);
        initIntervalsJSON.forEach((interval) => {
            sliderIntervals.push(interval.from)
            sliderIntervals.push(interval.to)
        });
        this.updateOptions(sliderIntervals);

        this.initSlider();
    },
    methods: {
        formatTo(value) {
            var m = Math.round(value) % 60;
            var h = Math.round((value - m) / 60);
            var hhmm = (h < 10 ? "0" : "") + h.toString() + ":" + (m < 10 ? "0" : "") + m.toString();
            return hhmm;
        },
        formatFrom(value) {
            var parts = value.split(":");
            var res = parts[0] * 60 + 1 * parts[1];
            return res;
        },
        mergeTooltips(slider, threshold, separator) {
            var textIsRtl = getComputedStyle(slider).direction === 'rtl';
            var isRtl = slider.noUiSlider.options.direction === 'rtl';
            var isVertical = slider.noUiSlider.options.orientation === 'vertical';
            var tooltips = slider.noUiSlider.getTooltips();
            var origins = slider.noUiSlider.getOrigins();

            // Move tooltips into the origin element. The default stylesheet handles this.
            tooltips.forEach(function (tooltip, index) {
                if (tooltip) {
                    origins[index].appendChild(tooltip);
                }
            });

            let that = this;
            slider.noUiSlider.on('update', function (values, handle, unencoded, tap, positions) {
                that.$emit('updated', values, handle)
                var pools = [[]];
                var poolPositions = [[]];
                var poolValues = [[]];
                var atPool = 0;

                // Assign the first tooltip to the first pool, if the tooltip is configured
                if (tooltips[0]) {
                    pools[0][0] = 0;
                    poolPositions[0][0] = positions[0];
                    poolValues[0][0] = values[0];
                }

                for (var i = 1; i < positions.length; i++) {
                    if (!tooltips[i] || (positions[i] - positions[i - 1]) > threshold) {
                        atPool++;
                        pools[atPool] = [];
                        poolValues[atPool] = [];
                        poolPositions[atPool] = [];
                    }

                    if (tooltips[i]) {
                        pools[atPool].push(i);
                        poolValues[atPool].push(values[i]);
                        poolPositions[atPool].push(positions[i]);
                    }
                }

                pools.forEach(function (pool, poolIndex) {
                    var handlesInPool = pool.length;

                    for (var j = 0; j < handlesInPool; j++) {
                        var handleNumber = pool[j];

                        if (j === handlesInPool - 1) {
                            var offset = 0;

                            poolPositions[poolIndex].forEach(function (value) {
                                offset += 1000 - value;
                            });

                            var direction = isVertical ? 'bottom' : 'right';
                            var last = isRtl ? 0 : handlesInPool - 1;
                            var lastOffset = 1000 - poolPositions[poolIndex][last];
                            offset = (textIsRtl && !isVertical ? 100 : 0) + (offset / handlesInPool) - lastOffset;

                            // Center this tooltip over the affected handles
                            tooltips[handleNumber].innerHTML = poolValues[poolIndex].join(separator);
                            tooltips[handleNumber].style.display = 'block';
                            tooltips[handleNumber].style[direction] = offset + '%';
                        } else {
                            // Hide this tooltip
                            tooltips[handleNumber].style.display = 'none';
                        }
                    }
                });
            });
        },

        initSlider() {
            let slider = this.$refs.slider;
            if (this.$refs.slider.noUiSlider) {
                this.$refs.slider.noUiSlider.destroy();
            }

            noUiSlider.create(slider, this.options);

            this.mergeTooltips(slider, "10", " ");
        },


        setIntervals(intervals) {
            this.$refs.slider.noUiSlider.off('update');
            this.updateOptions(intervals);
            this.initSlider();
        },

        updateOptions(intervals) {
            let connect = [];
            for (let i = 0; i < intervals.length + 1; i++) {
                connect.push(i % 2 ? true : false);
            }

            this.options.start = intervals;
            this.options.connect = connect;
        }
    }
}
</script>

<template>
    <div ref='slider'></div>
</template>

<style>
.slider {
    width: 550px;
    margin: 50px 15px;
}

.noUi-handle,
.noUi-target {
    box-shadow: none;
}

.noUi-horizontal {
    height: 9px;
}

.noUi-horizontal .noUi-handle {
    width: 14px;
    height: 14px;
    right: -7px;
    top: -3px;
}

.noUi-handle::before,
.noUi-handle::after {
    content: none
}

.noUi-handle {
    border-radius: 8px;
}

.noUi-marker-horizontal.noUi-marker-large {
    height: 8px;
}

.noUi-tooltip {
    border: none;
}

[data-bs-theme=dark] .noUi-tooltip, .noUi-connects, noUi-value{
    background-color: var(--tblr-bg-surface);
    border-color: var(--tblr-primary-border-subtle);
    color: rgba(var(--tblr-border-active-color), 0.5);
}

[data-bs-theme=dark] .noUi-horizontal {
    border-color: var(--tblr-border-color-active);
}

[data-bs-theme=dark] .noUi-handle {
    border-color: var(--tblr-border-active-color);
    background-color: var(--tblr-border-active-color);
}


.noUi-pips-horizontal {
    height: auto;
}

.noUi-pips {
    z-index: 0;
}


#saveActions a {
    margin-left: 2px;
}

</style>
