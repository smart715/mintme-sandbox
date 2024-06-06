export default {
    namespaced: true,
    state: {
        postRewardsCollectableDays: 30,
        commentTipCost: null,
        commentTipMinAmount: null,
        commentTipMaxAmount: null,
        isAuthorizedForReward: false,
        singlePost: null,
        comments: [],
        posts: [],
        tokenPostsAmount: 0,
    },
    getters: {
        getPostRewardsCollectableDays(state) {
            return state.postRewardsCollectableDays;
        },
        getCommentTipCost(state) {
            return state.commentTipCost;
        },
        getCommentTipMinAmount(state) {
            return state.commentTipMinAmount;
        },
        getCommentTipMaxAmount(state) {
            return state.commentTipMaxAmount;
        },
        getIsAuthorizedForReward(state) {
            return state.isAuthorizedForReward;
        },
        getSinglePost(state) {
            return state.singlePost;
        },
        getComments(state) {
            return state.comments;
        },
        getPosts(state) {
            return state.posts;
        },
        getTokenPostsAmount(state) {
            return state.tokenPostsAmount;
        },
    },
    mutations: {
        setPostRewardsCollectableDays(state, payload) {
            state.postRewardsCollectableDays = payload;
        },
        setCommentTipCost(state, payload) {
            state.commentTipCost = payload;
        },
        setCommentTipMinAmount(state, payload) {
            state.commentTipMinAmount = payload;
        },
        setCommentTipMaxAmount(state, payload) {
            state.commentTipMaxAmount = payload;
        },
        setIsAuthorizedForReward(state, payload) {
            state.isAuthorizedForReward = payload;
        },
        setSinglePost(state, payload) {
            state.singlePost = payload;
        },
        setComments(state, payload) {
            state.comments = payload;
        },
        likeSinglePost(state) {
            if (!state.singlePost) {
                return;
            }

            state.singlePost.likes++;
            state.singlePost.isUserAlreadyLiked = true;
        },
        removeSinglePostLike(state) {
            if (!state.singlePost) {
                return;
            }

            state.singlePost.likes--;
            state.singlePost.isUserAlreadyLiked = false;
        },
        addComment(state, payload) {
            state.comments.push(payload);

            if (state.singlePost) {
                state.singlePost.commentsCount++;
            }
        },
        removeCommentById(state, payload) {
            state.comments = state.comments.filter((comment) => comment.id !== payload);

            if (state.singlePost) {
                state.singlePost.commentsCount--;
            }
        },
        editComment(state, payload) {
            const commentIdx = state.comments.findIndex((c) => c.id === payload.id);

            if (-1 < commentIdx) {
                state.comments[commentIdx] = payload;
            }
        },
        setPosts(state, payload) {
            state.posts = payload;
        },
        addPost(state, payload) {
            if (!state.posts) {
                state.posts = [payload];
            } else {
                state.posts = [payload, ...state.posts];
            }

            state.tokenPostsAmount++;
        },
        deletePost(state, payload) {
            if (!state.posts) {
                return;
            }

            state.posts = state.posts.filter((p) => p.id !== payload.id);
            state.tokenPostsAmount--;
        },
        updatePost(state, payload) {
            if (!state.posts) {
                return;
            }

            const postIndex = state.posts.findIndex((p) => p.id === payload.id);

            if (-1 === postIndex) {
                return;
            }

            const posts = state.posts;
            posts[postIndex] = payload;

            state.posts = [...posts];
        },
        insertPosts(state, payload) {
            if (!state.posts) {
                state.posts = payload;
            } else {
                state.posts.push(...payload);
            }
        },
        setTokenPostsAmount(state, payload) {
            state.tokenPostsAmount = payload;
        },
        setCommentTipped(state, payload) {
            if (!state.comments) {
                return;
            }

            const commentIdx = state.comments.findIndex((c) => c.id === payload.id);

            if (-1 < commentIdx) {
                state.comments[commentIdx].tipped = true;
            }
        },
    },
};
