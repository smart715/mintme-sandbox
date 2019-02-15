<template>
    <div class="card">
        <div class="card-header">
            Shoutbox
            <span class="card-header-icon">
                <font-awesome-icon
                    icon="circle"
                    class="text-success text-xs"/>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="fix-height">
                <div class="chat px-3">
                    <span class="align-middle">
                        <img
                            src="../../../img/avatar.png"
                            alt="avatar">
                    </span>
                    <span class="pl-2 align-middle">
                        (12 : 44)
                        John: bla bla bla bla bla bla bla bla bla bla
                        bla bla bla bla bla bla bla bla bla bla
                        bla bla bla bla bla bla bla bla bla bla
                    </span>
                </div>
                <div class="chat  px-3">
                    <span class="align-middle">
                        <img
                            src="../../../img/avatar.png"
                            alt="avatar">
                    </span>
                    <span class="pl-2 align-middle">
                        (12 : 44)
                        John: bla bla bla bla bla bla bla bla bla bla
                        bla bla bla bla bla bla bla bla bla bla
                        bla bla bla bla bla bla bla bla bla bla
                    </span>
                </div>
                <div class="chat px-3">
                    <span class="align-middle">
                        <img
                            src="../../../img/avatar.png"
                            alt="avatar">
                    </span>
                    <span class="pl-2 align-middle">
                        (12 : 44)
                        John: bla bla bla bla bla bla bla bla bla bla
                        bla bla bla bla bla bla bla bla bla bla
                        bla bla bla bla bla bla bla bla bla bla
                    </span>
                </div>
            </div>
            <div class="py-2  px-3">
                <textarea class="form-control"></textarea>
            </div>
            <div class="text-center pb-2">
                <a class="c-pointer">
                    <img
                        src="../../../img/down-arrows.png"
                        class="icon-arrows-down"
                        alt="arrow">
                </a>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TokenShoutBox',
};
</script>

