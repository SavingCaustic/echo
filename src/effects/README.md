# Effects

Effects are normally initialized by the rack but may also be used by a synth or another effect.
Therefore, there's no reference to rack, only dspCore. Buffer in the dsp is currently *not*
passed a pointer. This could possibly change..