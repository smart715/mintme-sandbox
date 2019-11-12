const _ = require('lodash');

const ZIP_REGEX_LIB = {
    ad: /^[a-z]{2}\d{3}$/,
    af: /^\d{4}$/,
    ai: /^[a-z]{2}\-\d{4}$/,
    al: /^\d{4}$/,
    am: /^\d{4}$/,
    aq: /^[a-z]{4}\s?\d[a-z]{2}$/,
    ar: /^((\d{4})|([a-z]{1}\d{4}[a-z]{3})|(\d{4}\s([a-z]{1}\d{4}[a-z]{3})?))$/,
    as: /^\d{5}(\-\d{4})?$/,
    at: /^\d{4}$/,
    au: /^\d{4}$/,
    ax: /^([a-z]{2}\-)?\d{5}$/,
    az: /^([a-z]{2})?\s?\d{4}$/,
    ba: /^\d{5}$/,
    bb: /^[a-z]{2}\d{5}$/,
    bd: /^\d{4}$/,
    be: /^\d{4}$/,
    bg: /^\d{4}$/,
    bh: /^\d{3,4}$/,
    bj: /^\d{6}$/,
    bl: /^\d{5}$/,
    bm: /^[a-z]{2}\s\d{2}$/,
    bn: /^[a-z]{2}\d{4}$/,
    br: /^\d{5}(\-\d{3})?$/,
    bt: /^\d{5}$/,
    by: /^\d{6}$/,
    ca: /^[a-z]{1}\d{1}[a-z]{1}\s?\d{1}[a-z]{1}\d{1}$/,
    cc: /^\d{4}$/,
    ch: /^\d{4}$/,
    cl: /^\d{3}\-?\d{4}$/,
    cn: /^\d{6}$/,
    co: /^\d{6}$/,
    cr: /^((\d{4,5})|(\d{5}\-\d{4}))$/,
    cs: /^\d{5}$/,
    cu: /^\d{5}$/,
    cv: /^\d{4}$/,
    cx: /^\d{4}$/,
    cy: /^\d{4}$/,
    cz: /^\d{3}\s?\d{2}$/,
    de: /^\d{5}$/,
    dk: /^\d{4}$/,
    do: /^\d{5}$/,
    dz: /^\d{5}$/,
    ec: /^\d{6}$/,
    ee: /^\d{5}$/,
    eg: /^\d{5}$/,
    es: /^\d{5}$/,
    et: /^\d{4}$/,
    fi: /^\d{5}$/,
    fk: /^[a-z]{4}\s?\d[a-z]{2}$/,
    fm: /^\d{5}(\-\d{4})?$/,
    fo: /^([a-z]{2}-)?(\d{3})$/,
    fr: /^((0[1-9])|([1-8]\d)|(9[0-8])|(2a)|(2b))\d{3}$/,
    gb: /^[a-z]([a-z])?\d(([a-z])|(\d))?(\s\d[a-z]{2})?$/,
    ge: /^\d{4}$/,
    gf: /^\d{5}$/,
    gg: /^[a-z]{2}\d\d?\s?\d[a-z]{2}$/,
    gi: /^[a-z]{2}\d{2}\s?\d[a-z]{2}$/,
    gl: /^\d{4}$/,
    gn: /^\d{3}$/,
    gp: /^\d{5}$/,
    gr: /^\d{3}\s?\d{2}$/,
    gs: /^[a-z]{4}\s?\d[a-z]{2}$/,
    gt: /^\d{5}$/,
    gu: /^\d{5}(\-\d{4})?$/,
    gw: /^\d{4}$/,
    hk: /^\d{5}$/,
    hn: /^((\d{5})|([a-z]{2}\d{4}))$/,
    hr: /^\d{5}$/,
    ht: /^\d{4}$/,
    hu: /^\d{4}$/,
    ie: /^(([a-z]{2}(\s(([a-z0-9]{1})|(\d{2})))?)|([a-z]{3}))$/,
    ic: /^\d{5}$/,
    id: /^\d{5}$/,
    il: /^\d{5}(\d{2})?$/,
    im: /^[a-z]{2}\d\d?\s?\d[a-z]{2}$/,
    in: /^\d{3}\s?\d{3}$/,
    io: /^[a-z]{4}\s?\d[a-z]{2}$/,
    iq: /^\d{5}$/,
    ir: /^\d{10}$/,
    is: /^\d{3}$/,
    it: /^\d{5}$/,
    je: /^[a-z]{2}\d\d?\s?\d[a-z]{2}$/,
    jm: /^\d{2}$/,
    jo: /^\d{5}$/,
    jp: /^\d{3}\-?\d{4}$/,
    ke: /^\d{5}$/,
    kh: /^\d{5}$/,
    kg: /^\d{6}$/,
    kr: /^\d{5}$/,
    kw: /^\d{5}$/,
    ky: /^[a-z]{2}\d\-\d{4}$/,
    kz: /^\d{6}$/,
    la: /^\d{5}$/,
    lb: /^((\d{5})|(\d{4}\s\d{4}))$/,
    lc: /^[a-z]{2}\d{2}\s?\d{3}$/,
    li: /^\d{4}$/,
    lk: /^\d{5}$/,
    lr: /^\d{4}$/,
    ls: /^\d{3}$/,
    lt: /^([a-z]{2}\-)?(\d{5})$/,
    lu: /^\d{4}$/,
    lv: /^([a-z]{2}\-)?(\d{4})$/,
    ma: /^\d{5}$/,
    mc: /^\d{5}$/,
    md: /^([a-z]{2}(\-)?)?\d{4}$/,
    me: /^\d{5}$/,
    mf: /^\d{5}$/,
    mg: /^\d{3}$/,
    mh: /^\d{5}(\-\d{4})?$/,
    mk: /^\d{4}$/,
    mm: /^\d{5}$/,
    mn: /^\d{5,6}$/,
    mp: /^\d{5}(\-\d{4})?$/,
    mq: /^\d{5}$/,
    mt: /^[a-z]{3}\s\d{2,4}$/,
    mu: /^\d{5}$/,
    mv: /^\d{4,5}$/,
    mx: /^\d{5}$/,
    my: /^\d{5}$/,
    mz: /^\d{4}$/,
    nc: /^\d{5}$/,
    ne: /^\d{4}$/,
    nf: /^\d{4}$/,
    ng: /^\d{4}$/,
    ni: /^\d{5}$/,
    nl: /^(\d{4})\s?[a-z]{2}$/,
    no: /^\d{4}$/,
    np: /^\d{5}$/,
    nz: /^\d{4}$/,
    om: /^\d{3}$/,
    pa: /^\d{4}$/,
    pe: /^((\d{5})|([a-z]{2}\s\d{4}))$/,
    pf: /^\d{5}$/,
    pg: /^\d{3}$/,
    ph: /^\d{4}$/,
    pk: /^\d{5}$/,
    pl: /^\d{2}(-)?\d{3}$/,
    pm: /^\d{5}$/,
    pn: /^[a-z]{4}\s?\d[a-z]{2}$/,
    pr: /^\d{5}(\-\d{4})?$/,
    ps: /^\d{3}$/,
    pt: /^\d{4}(\-\d{3})?$/,
    pw: /^\d{5}(\-\d{4})?$/,
    py: /^\d{4}$/,
    re: /^\d{5}$/,
    ro: /^\d{6}$/,
    rs: /^\d{5,6}$/,
    ru: /^\d{6}$/,
    sa: /^\d{5}(\-\d{4})?$/,
    sd: /^\d{4,5}$/,
    se: /^\d{3}\s?\d{2}$/,
    sg: /^\d{2,6}$/,
    sh: /^[a-z]{4}\s?\d[a-z]{2}$/,
    si: /^([a-z]{2}\-)?\d{4}$/,
    sj: /^\d{4}$/,
    sk: /^\d{3}\s?\d{2}$/,
    sm: /^\d{5}$/,
    sn: /^\d{5}$/,
    so: /^[a-z]{2}\s?\d{5}$/,
    sv: /^\d{4}$/,
    sz: /^[a-z]{1}\d{3}$/,
    tc: /^[a-z]{4}\s?\d[a-z]{2}$/,
    th: /^\d{5}$/,
    tj: /^\d{6}$/,
    tm: /^\d{6}$/,
    tn: /^\d{4}$/,
    tr: /^\d{5}$/,
    tt: /^\d{6}$/,
    tw: /^\d{3}((\-)?\d{2})?$/,
    tz: /^\d{5}$/,
    ua: /^\d{5}$/,
    um: /^\d{5}$/,
    us: /^\d{5}(-\d{4})?$/,
    uy: /^\d{5}$/,
    uz: /^\d{6}$/,
    va: /^\d{5}$/,
    vc: /^[a-z]{2}\d{4}$/,
    ve: /^\d{4}(\-[a-z])?$/,
    vg: /^[a-z]{2}\d{4}$/,
    vi: /^\d{5}(-\d{4})?$/,
    vn: /^\d{5,6}$/,
    wf: /^\d{5}$/,
    ws: /^[a-z]{2}\d{4}$/,
    xk: /^\d{5}$/,
    yt: /^\d{5}$/,
    yu: /^\d{5}$/,
    za: /^\d{4}$/,
    zm: /^\d{5}$/,
};

/**
 * Checks zip code for chosen country
 * @param {string} countryCode
 * @param {string} zipCode
 * @return {boolean} whether is valid or not
 */
function zipCodeValidate(countryCode, zipCode) {
    let countryCodeId;
    let zipCodeId;

    if (!_.isString(countryCode) || !_.isString(zipCode)) {
        return false;
    }

    countryCodeId = _.toLower(_.trim(countryCode));
    zipCodeId = _.toLower(_.trim(zipCode));

    if (-1 === _.indexOf(_.keys(ZIP_REGEX_LIB), countryCodeId)) {
        return false;
    }

    return ZIP_REGEX_LIB[countryCodeId].test(zipCodeId);
}

/**
 * Checks chosen country for zip codes existing
 * @param {string} countryCode
 * @return {boolean} codes are exist or not
 */
function zipCodeAvailable(countryCode) {
    let countryCodeId = _.toLower(_.trim(countryCode));

    if ('' === countryCodeId) {
        return false;
    }

    return -1 !== _.indexOf(_.keys(ZIP_REGEX_LIB), countryCodeId);
}

export {
    zipCodeAvailable,
    zipCodeValidate,
};
