/* Unit 4: Operators and Expression
 * Increment/decrement operators (++, --), pre vs post forms. */
#include <stdio.h>

int main(void) {
    int a = 5;

    printf("a++ gives %d, now a = %d\n", a++, a);
    printf("++a gives %d, now a = %d\n", ++a, a);
    printf("a-- gives %d, now a = %d\n", a--, a);
    printf("--a gives %d, now a = %d\n", --a, a);

    return 0;
}
