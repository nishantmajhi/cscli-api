/* Unit 9: Pointers
 * Chain of pointers - a pointer to a pointer. */
#include <stdio.h>

int main(void) {
    int num = 7;
    int *p = &num;
    int **pp = &p; /* pointer to a pointer */

    printf("num            = %d\n", num);
    printf("*p             = %d\n", *p);
    printf("**pp           = %d\n", **pp);

    return 0;
}
