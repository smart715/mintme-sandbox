<template>

</template>

<script>
    export default {
        name: 'TawkChat',
        porps: {
            username: String,
            hash: String,
        },
        mounted() {
            let Tawk_API=Tawk_API||{};
            Tawk_API.visitor = {
                name : this.username,
                email : this.username,
                hash : this.hash,
            };
            let Tawk_LoadStart=new Date();
            (function(){
                var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
                s1.async=true;
                s1.src='https://embed.tawk.to/5c66aba9a6c3590a404d5f2d/default';
                s1.charset='UTF-8';
                s1.setAttribute('crossorigin','*');
                s0.parentNode.insertBefore(s1,s0);
            })();
        },
    };
</script>
