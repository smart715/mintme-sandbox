<template>
    <div class="card">
        <div class="card-header">
            Shoutbox
            <span class="card-header-icon">
                <guide>
                    <template slot="header">
                        Trade History
                    </template>
                    <template slot="body">
                        List of last closed orders for {{ tokenName }}.
                    </template>
                </guide>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="fix-height">
                <div class="chat pl-2">
                    <img
                            src="../../../img/avatar.png"
                            class=""
                            alt="avatar">
                    (12 : 44)
                    John: bla bla bla bla bla bla bla bla bla bla
                    bla bla bla bla bla bla bla bla bla bla
                    bla bla bla bla bla bla bla bla bla bla
                </div>
                <div class="chat pl-2">
                    <img
                            src="../../../img/avatar.png"
                            class=""
                            alt="avatar">
                    (12 : 44)
                    John: bla bla bla bla bla bla bla bla bla bla
                    bla bla bla bla bla bla bla bla bla bla
                    bla bla bla bla bla bla bla bla bla bla
                </div>
                <div class="chat pl-2">
                    <img
                            src="../../../img/avatar.png"
                            class=""
                            alt="avatar">
                    (12 : 44)
                    John: bla bla bla bla bla bla bla bla bla bla
                    bla bla bla bla bla bla bla bla bla bla
                    bla bla bla bla bla bla bla bla bla bla
                </div>
            </div>
            <div class="pt-2">
                <textarea class="form-control"></textarea>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TokenShoutBox',
};
</script>

